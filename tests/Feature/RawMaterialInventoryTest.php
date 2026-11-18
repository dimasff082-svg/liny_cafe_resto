<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawMaterialInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $warehouse;

    protected User $admin;

    protected User $owner;

    protected User $cashier;

    protected User $kitchen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = User::factory()->create([
            'role' => 'warehouse',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->kitchen = User::factory()->create([
            'role' => 'kitchen',
            'is_active' => true,
        ]);
    }

    public function test_warehouse_staff_can_view_raw_materials_index_and_kpis(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-001',
            'name' => 'Biji Kopi Arabika',
            'category' => 'Biji Kopi',
            'unit' => 'kg',
            'current_stock' => 10,
            'min_stock' => 3,
            'unit_price' => 150000,
        ]);

        $response = $this->actingAs($this->warehouse)->get(route('warehouse.raw_materials.index'));

        $response->assertStatus(200);
        $response->assertSee('Inventori Bahan Baku Kafe');
        $response->assertSee('Biji Kopi Arabika');
        $response->assertSee('BHN-001');
        $response->assertSee('Rp 1.500.000'); // Total aset
    }

    public function test_admin_and_owner_can_access_raw_materials_index(): void
    {
        $this->actingAs($this->admin)->get(route('warehouse.raw_materials.index'))->assertStatus(200);
        $this->actingAs($this->owner)->get(route('warehouse.raw_materials.index'))->assertStatus(200);
    }

    public function test_unauthorized_roles_cannot_access_raw_materials(): void
    {
        $this->actingAs($this->cashier)->get(route('warehouse.raw_materials.index'))->assertStatus(403);
        $this->actingAs($this->kitchen)->get(route('warehouse.raw_materials.index'))->assertStatus(403);
    }

    public function test_can_create_raw_material_with_auto_generated_code_and_initial_stock(): void
    {
        $response = $this->actingAs($this->warehouse)->post(route('warehouse.raw_materials.store'), [
            'name' => 'Fresh Milk Diamond',
            'category' => 'Susu & Dairy',
            'unit' => 'liter',
            'current_stock' => 12,
            'min_stock' => 4,
            'unit_price' => '22.000',
            'notes' => 'Susu segar kemasan karton 1L',
        ]);

        $response->assertRedirect(route('warehouse.raw_materials.index'));
        $response->assertSessionHas('success');

        $material = RawMaterial::where('name', 'Fresh Milk Diamond')->first();
        $this->assertNotNull($material);
        $this->assertStringStartsWith('BHN-', $material->code);
        $this->assertEquals(12.00, (float) $material->current_stock);
        $this->assertEquals(22000.00, (float) $material->unit_price);

        // Memastikan mutasi saldo awal tercatat
        $mutation = RawMaterialMutation::where('raw_material_id', $material->id)->first();
        $this->assertNotNull($mutation);
        $this->assertEquals('in', $mutation->type);
        $this->assertEquals('in', $mutation->mutation_type);
        $this->assertEquals(12.00, (float) $mutation->quantity);
        $this->assertEquals('INITIAL-STOCK', $mutation->reference_number);
    }

    public function test_can_update_raw_material_master_info(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-050',
            'name' => 'Sirup Karamel Monin',
            'category' => 'Sirup',
            'unit' => 'botol',
            'current_stock' => 5,
            'min_stock' => 2,
            'unit_price' => 140000,
        ]);

        $response = $this->actingAs($this->warehouse)->put(route('warehouse.raw_materials.update', $material->id), [
            'code' => 'BHN-050',
            'name' => 'Sirup Karamel Monin Premium 700ml',
            'category' => 'Sirup & Perisa',
            'unit' => 'botol',
            'min_stock' => 3,
            'unit_price' => '145.000',
            'notes' => 'Update nama dan harga distributor baru',
        ]);

        $response->assertRedirect(route('warehouse.raw_materials.index'));
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals('Sirup Karamel Monin Premium 700ml', $material->name);
        $this->assertEquals('Sirup & Perisa', $material->category);
        $this->assertEquals(145000.00, (float) $material->unit_price);
        $this->assertEquals(3.00, (float) $material->min_stock);
    }

    public function test_can_restock_raw_material_and_update_unit_price(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-010',
            'name' => 'Paper Cup Hot 8oz',
            'category' => 'Packaging',
            'unit' => 'pcs',
            'current_stock' => 50,
            'min_stock' => 20,
            'unit_price' => 500,
        ]);

        $response = $this->actingAs($this->warehouse)->post(route('warehouse.raw_materials.restock', $material->id), [
            'quantity' => 200,
            'unit_price' => '550',
            'reference_number' => 'NOTA-RESTOCK-01',
            'notes' => 'Pembelian paper cup dari agen',
        ]);

        $response->assertRedirect(route('warehouse.raw_materials.index'));
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(250.00, (float) $material->current_stock);
        $this->assertEquals(550.00, (float) $material->unit_price);

        $mutation = RawMaterialMutation::where('raw_material_id', $material->id)->latest('id')->first();
        $this->assertEquals('in', $mutation->mutation_type);
        $this->assertEquals(200.00, (float) $mutation->quantity);
        $this->assertEquals(50.00, (float) $mutation->stock_before);
        $this->assertEquals(250.00, (float) $mutation->stock_after);
        $this->assertEquals('NOTA-RESTOCK-01', $mutation->reference_number);
    }

    public function test_can_record_usage_with_stock_reduction(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-002',
            'name' => 'Biji Kopi Robusta',
            'category' => 'Biji Kopi',
            'unit' => 'kg',
            'current_stock' => 10,
            'min_stock' => 3,
            'unit_price' => 100000,
        ]);

        $response = $this->actingAs($this->warehouse)->post(route('warehouse.raw_materials.usage', $material->id), [
            'quantity' => 2.5,
            'usage_type' => 'bar_usage',
            'notes' => 'Pemakaian barista shift pagi',
        ]);

        $response->assertRedirect(route('warehouse.raw_materials.index'));
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(7.50, (float) $material->current_stock);

        $mutation = RawMaterialMutation::where('raw_material_id', $material->id)->latest('id')->first();
        $this->assertEquals('out', $mutation->mutation_type);
        $this->assertEquals(2.50, (float) $mutation->quantity);
        $this->assertEquals(10.00, (float) $mutation->stock_before);
        $this->assertEquals(7.50, (float) $mutation->stock_after);
    }

    public function test_cannot_record_usage_exceeding_physical_stock(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-003',
            'name' => 'Matcha Powder',
            'category' => 'Powder',
            'unit' => 'pack',
            'current_stock' => 3,
            'min_stock' => 1,
            'unit_price' => 150000,
        ]);

        // Coba mengeluarkan 5 pack padahal stok cuma 3
        $response = $this->actingAs($this->warehouse)->post(route('warehouse.raw_materials.usage', $material->id), [
            'quantity' => 5,
            'usage_type' => 'bar_usage',
            'notes' => 'Pakai berlebih',
        ]);

        $response->assertSessionHas('error');

        $material->refresh();
        $this->assertEquals(3.00, (float) $material->current_stock); // Stok tetap aman
    }

    public function test_can_record_stock_opname_physical_audit(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-004',
            'name' => 'Gula Aren Cair',
            'category' => 'Gula',
            'unit' => 'liter',
            'current_stock' => 10,
            'min_stock' => 3,
            'unit_price' => 40000,
        ]);

        // Audit fisik di bar menemukan stok riil adalah 8.5 liter (selisih -1.5 liter)
        $response = $this->actingAs($this->warehouse)->post(route('warehouse.raw_materials.opname', $material->id), [
            'physical_stock' => 8.5,
            'notes' => 'Audit opname mingguan',
        ]);

        $response->assertRedirect(route('warehouse.raw_materials.index'));
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(8.50, (float) $material->current_stock);

        $mutation = RawMaterialMutation::where('raw_material_id', $material->id)->latest('id')->first();
        $this->assertEquals('adjustment', $mutation->mutation_type);
        $this->assertEquals(1.50, (float) $mutation->quantity); // Selisih 1.5
        $this->assertEquals(10.00, (float) $mutation->stock_before);
        $this->assertEquals(8.50, (float) $mutation->stock_after);
    }

    public function test_can_view_individual_stock_card_history_and_global_mutations(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-005',
            'name' => 'Sirup Vanilla',
            'category' => 'Sirup',
            'unit' => 'botol',
            'current_stock' => 4,
            'min_stock' => 2,
            'unit_price' => 140000,
        ]);

        $material->recordMutation('in', 4, 'Saldo Awal', 'INIT-01', $this->warehouse->id);

        // 1. Kartu stok item spesifik
        $historyRes = $this->actingAs($this->warehouse)->get(route('warehouse.raw_materials.history', $material->id));
        $historyRes->assertStatus(200);
        $historyRes->assertSee('Kartu Stok: Sirup Vanilla');
        $historyRes->assertSee('INIT-01');

        // 2. Buku mutasi stok global
        $mutationsRes = $this->actingAs($this->warehouse)->get(route('warehouse.raw_materials.mutations'));
        $mutationsRes->assertStatus(200);
        $mutationsRes->assertSee('Buku Riwayat Mutasi Stok');
        $mutationsRes->assertSee('Sirup Vanilla');
    }

    public function test_recording_expense_with_raw_material_automatically_restocks_inventory(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-001',
            'name' => 'Biji Kopi Arabika',
            'category' => 'Biji Kopi',
            'unit' => 'kg',
            'current_stock' => 5,
            'min_stock' => 2,
            'unit_price' => 180000,
        ]);

        $response = $this->actingAs($this->warehouse)->post(route('warehouse.expenses.store'), [
            'expense_type' => 'raw_material',
            'raw_material_id' => $material->id,
            'raw_material_quantity' => 10,
            'title' => 'Pembelian Biji Kopi Arabika 10kg',
            'category' => 'Bahan Baku',
            'amount' => '1.800.000',
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'notes' => 'Beli dari roastery langsung',
        ]);

        $response->assertRedirect(route('warehouse.expenses.index'));
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertEquals(15.00, (float) $material->current_stock); // 5 + 10 = 15

        $expense = Expense::where('title', 'Pembelian Biji Kopi Arabika 10kg')->first();
        $this->assertNotNull($expense);
        $this->assertEquals($material->id, $expense->raw_material_id);
        $this->assertEquals(10.00, (float) $expense->raw_material_quantity);

        // Cek mutasi otomatis
        $mutation = RawMaterialMutation::where('raw_material_id', $material->id)->latest('id')->first();
        $this->assertEquals('in', $mutation->mutation_type);
        $this->assertEquals(10.00, (float) $mutation->quantity);
        $this->assertEquals($expense->expense_number, $mutation->reference_number);
    }

    public function test_deleting_expense_reverts_automatically_added_raw_material_stock(): void
    {
        $material = RawMaterial::create([
            'code' => 'BHN-001',
            'name' => 'Biji Kopi Arabika',
            'category' => 'Biji Kopi',
            'unit' => 'kg',
            'current_stock' => 5,
            'min_stock' => 2,
            'unit_price' => 180000,
        ]);

        // Simpan expense dan auto restock
        $this->actingAs($this->warehouse)->post(route('warehouse.expenses.store'), [
            'expense_type' => 'raw_material',
            'raw_material_id' => $material->id,
            'raw_material_quantity' => 8,
            'title' => 'Salah Input Pembelian Biji Kopi',
            'category' => 'Bahan Baku',
            'amount' => '1.440.000',
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
        ]);

        $material->refresh();
        $this->assertEquals(13.00, (float) $material->current_stock); // 5 + 8 = 13

        $expense = Expense::where('title', 'Salah Input Pembelian Biji Kopi')->first();

        // Hapus expense karena salah input
        $deleteRes = $this->actingAs($this->warehouse)->delete(route('warehouse.expenses.destroy', $expense->id));
        $deleteRes->assertRedirect(route('warehouse.expenses.index'));

        // Stok ditarik kembali (revert)
        $material->refresh();
        $this->assertEquals(5.00, (float) $material->current_stock); // 13 - 8 = 5

        $revertMutation = RawMaterialMutation::where('raw_material_id', $material->id)->latest('id')->first();
        $this->assertEquals('out', $revertMutation->mutation_type);
        $this->assertEquals(8.00, (float) $revertMutation->quantity);
        $this->assertStringContainsString('REVERT-', $revertMutation->reference_number);
    }

    public function test_status_helpers_low_stock_and_out_of_stock(): void
    {
        $outItem = new RawMaterial(['current_stock' => 0, 'min_stock' => 5, 'unit_price' => 10000]);
        $this->assertTrue($outItem->isOutOfStock());
        $this->assertFalse($outItem->isLowStock());
        $this->assertEquals('Habis', $outItem->stockStatusLabel());

        $lowItem = new RawMaterial(['current_stock' => 3, 'min_stock' => 5, 'unit_price' => 20000]);
        $this->assertFalse($lowItem->isOutOfStock());
        $this->assertTrue($lowItem->isLowStock());
        $this->assertEquals('Menipis', $lowItem->stockStatusLabel());
        $this->assertEquals(60000.0, $lowItem->totalAssetValue());

        $safeItem = new RawMaterial(['current_stock' => 10, 'min_stock' => 5, 'unit_price' => 20000]);
        $this->assertFalse($safeItem->isOutOfStock());
        $this->assertFalse($safeItem->isLowStock());
        $this->assertTrue($safeItem->isSafeStock());
        $this->assertEquals('Aman', $safeItem->stockStatusLabel());
    }
}
