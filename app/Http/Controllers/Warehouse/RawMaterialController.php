<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Models\RawMaterialMutation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RawMaterialController extends Controller
{
    /**
     * Menampilkan katalog stok bahan baku kafe beserta 4 kartu KPI.
     */
    public function index(Request $request): View
    {
        $query = RawMaterial::query();

        // 1. Filter Pencarian Nama / Kode / Kategori
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // 2. Filter Kategori
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        // 3. Filter Kondisi Stok (Aman, Menipis, Habis)
        if ($stockStatus = $request->input('stock_status')) {
            if ($stockStatus === 'low') {
                $query->where('current_stock', '>', 0)
                    ->whereColumn('current_stock', '<=', 'min_stock');
            } elseif ($stockStatus === 'out') {
                $query->where('current_stock', '<=', 0);
            } elseif ($stockStatus === 'safe') {
                $query->where('current_stock', '>', 0)
                    ->whereColumn('current_stock', '>', 'min_stock');
            }
        }

        $rawMaterials = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        // 4. Kalkulasi KPI Ringkasan Stok
        $allMaterials = RawMaterial::all();
        $totalItems = $allMaterials->count();
        $totalAssetValue = (float) $allMaterials->sum(fn ($item) => (float) $item->current_stock * (float) $item->unit_price);
        $lowStockCount = $allMaterials->filter(fn ($item) => $item->isLowStock())->count();
        $outOfStockCount = $allMaterials->filter(fn ($item) => $item->isOutOfStock())->count();

        $summary = [
            'total_items' => $totalItems,
            'total_asset_value' => $totalAssetValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];

        // Daftar kategori unik untuk dropdown filter
        $categories = RawMaterial::distinct()->pluck('category')->filter()->values();
        if ($categories->isEmpty()) {
            $categories = collect([
                'Biji Kopi',
                'Susu & Dairy',
                'Sirup & Perisa',
                'Bubuk & Powder',
                'Topping & Gula',
                'Cup & Packaging',
                'Perlengkapan Bar',
            ]);
        }

        return view('warehouse.raw_materials.index', compact('rawMaterials', 'summary', 'categories'));
    }

    /**
     * Menyimpan data master bahan baku baru.
     */
    public function store(Request $request): RedirectResponse
    {
        // Bersihkan tanda titik ribuan pada harga beli jika ada
        if ($request->has('unit_price')) {
            $cleanPrice = preg_replace('/[^\d]/', '', (string) $request->input('unit_price'));
            $request->merge(['unit_price' => $cleanPrice !== '' ? (float) $cleanPrice : 0]);
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:raw_materials,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama bahan baku wajib diisi.',
            'category.required' => 'Kategori bahan baku wajib dipilih atau diisi.',
            'unit.required' => 'Satuan ukuran bahan wajib diisi.',
            'code.unique' => 'Kode bahan baku sudah digunakan.',
        ]);

        $code = ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : RawMaterial::generateCode();
        $initialStock = isset($validated['current_stock']) ? (float) $validated['current_stock'] : 0;
        $unitPrice = isset($validated['unit_price']) ? (float) $validated['unit_price'] : 0;
        $minStock = isset($validated['min_stock']) ? (float) $validated['min_stock'] : 0;

        $rawMaterial = RawMaterial::create([
            'code' => $code,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'current_stock' => 0, // Set 0 dulu, jika ada initialStock dicatat via recordMutation
            'min_stock' => $minStock,
            'unit_price' => $unitPrice,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($initialStock > 0) {
            $rawMaterial->recordMutation(
                type: 'in',
                quantity: $initialStock,
                notes: 'Saldo awal pendaftaran bahan baku baru',
                referenceNumber: 'INITIAL-STOCK',
                userId: $request->user()->id,
                unitPrice: $unitPrice
            );
        }

        return redirect()->route('warehouse.raw_materials.index')
            ->with('success', "Bahan baku [{$code}] {$rawMaterial->name} berhasil ditambahkan ke inventori.");
    }

    /**
     * Memperbarui informasi data master bahan baku (tanpa mengubah stok fisik langsung).
     */
    public function update(Request $request, RawMaterial $rawMaterial): RedirectResponse
    {
        if ($request->has('unit_price')) {
            $cleanPrice = preg_replace('/[^\d]/', '', (string) $request->input('unit_price'));
            $request->merge(['unit_price' => $cleanPrice !== '' ? (float) $cleanPrice : 0]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:raw_materials,code,'.$rawMaterial->id],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama bahan baku wajib diisi.',
            'category.required' => 'Kategori bahan baku wajib dipilih.',
            'unit.required' => 'Satuan bahan baku wajib diisi.',
            'code.required' => 'Kode bahan baku wajib diisi.',
            'code.unique' => 'Kode bahan baku sudah digunakan oleh item lain.',
        ]);

        $rawMaterial->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => $validated['name'],
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'min_stock' => isset($validated['min_stock']) ? (float) $validated['min_stock'] : 0,
            'unit_price' => isset($validated['unit_price']) ? (float) $validated['unit_price'] : $rawMaterial->unit_price,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('warehouse.raw_materials.index')
            ->with('success', "Data bahan baku {$rawMaterial->name} berhasil diperbarui.");
    }

    /**
     * Menghapus bahan baku dari katalog inventori.
     */
    public function destroy(RawMaterial $rawMaterial): RedirectResponse
    {
        $name = $rawMaterial->name;
        $rawMaterial->delete();

        return redirect()->route('warehouse.raw_materials.index')
            ->with('success', "Bahan baku {$name} beserta riwayat kartunya berhasil dihapus.");
    }

    /**
     * Restock Cepat (+): Menambah stok bahan masuk secara mandiri.
     */
    public function restock(Request $request, RawMaterial $rawMaterial): RedirectResponse
    {
        if ($request->has('unit_price')) {
            $cleanPrice = preg_replace('/[^\d]/', '', (string) $request->input('unit_price'));
            $request->merge(['unit_price' => $cleanPrice !== '' ? (float) $cleanPrice : null]);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'Jumlah kuantitas barang masuk wajib diisi.',
            'quantity.min' => 'Jumlah barang masuk minimal 0.01.',
        ]);

        $qty = (float) $validated['quantity'];
        $unitPrice = isset($validated['unit_price']) ? (float) $validated['unit_price'] : null;
        $ref = $validated['reference_number'] ?? ('RESTOCK-'.now()->format('YmdHis'));
        $notes = $validated['notes'] ?? 'Restock masuk mandiri ke gudang/bar';

        $rawMaterial->recordMutation(
            type: 'in',
            quantity: $qty,
            notes: $notes,
            referenceNumber: $ref,
            userId: $request->user()->id,
            unitPrice: $unitPrice
        );

        return redirect()->route('warehouse.raw_materials.index')
            ->with('success', "Berhasil menambahkan stok masuk {$qty} {$rawMaterial->unit} untuk {$rawMaterial->name}.");
    }

    /**
     * Catat Pemakaian Bar/Rusak (-): Mengurangi stok fisik bahan baku dengan validasi sisa stok.
     */
    public function usage(Request $request, RawMaterial $rawMaterial): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'usage_type' => ['required', 'string', 'in:bar_usage,kitchen_usage,damaged_spill,expired,other'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'Jumlah pemakaian/pengurangan bahan wajib diisi.',
            'quantity.min' => 'Jumlah pemakaian minimal 0.01.',
        ]);

        $qty = (float) $validated['quantity'];
        $currentStock = (float) $rawMaterial->current_stock;

        // Validasi: jumlah yang dikeluarkan tidak boleh melebihi sisa stok fisik
        if ($qty > $currentStock) {
            return back()->withInput()->with('error', "Gagal mencatat pemakaian: Jumlah yang dikeluarkan ({$qty} {$rawMaterial->unit}) melebihi sisa stok fisik saat ini ({$currentStock} {$rawMaterial->unit}).");
        }

        $usageLabel = match ($validated['usage_type']) {
            'bar_usage' => 'Pemakaian Operasional Bar Minuman',
            'kitchen_usage' => 'Pemakaian Operasional Dapur & Pastry',
            'damaged_spill' => 'Bahan Rusak / Tumpah (Waste / Spill)',
            'expired' => 'Bahan Kadaluarsa / Basi',
            default => 'Pemakaian Bahan Baku Lainnya',
        };

        $notes = $usageLabel;
        if (! empty($validated['notes'])) {
            $notes .= ' - '.$validated['notes'];
        }

        $ref = 'USE-'.now()->format('YmdHis');

        $rawMaterial->recordMutation(
            type: 'out',
            quantity: $qty,
            notes: $notes,
            referenceNumber: $ref,
            userId: $request->user()->id
        );

        return redirect()->route('warehouse.raw_materials.index')
            ->with('success', "Berhasil mencatat pemakaian {$qty} {$rawMaterial->unit} untuk {$rawMaterial->name}.");
    }

    /**
     * Stock Opname Fisik: Formulir audit fisik berkala yang secara otomatis menghitung selisih (+/-).
     */
    public function opname(Request $request, RawMaterial $rawMaterial): RedirectResponse
    {
        $validated = $request->validate([
            'physical_stock' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'physical_stock.required' => 'Jumlah hitungan stok fisik nyata wajib diisi.',
            'physical_stock.min' => 'Hitungan stok fisik tidak boleh bernilai negatif.',
        ]);

        $physicalStock = (float) $validated['physical_stock'];
        $systemStock = (float) $rawMaterial->current_stock;
        $diff = $physicalStock - $systemStock;

        $diffText = $diff >= 0 ? '+'.number_format($diff, 2, ',', '.') : number_format($diff, 2, ',', '.');
        $notes = "Stock Opname Fisik (Hitungan Nyata: {$physicalStock} {$rawMaterial->unit}, Sistem: {$systemStock} {$rawMaterial->unit}, Selisih: {$diffText} {$rawMaterial->unit})";
        if (! empty($validated['notes'])) {
            $notes .= ' - '.$validated['notes'];
        }

        $ref = 'OPNAME-'.now()->format('YmdHis');

        $rawMaterial->recordMutation(
            type: 'adjustment',
            quantity: $physicalStock,
            notes: $notes,
            referenceNumber: $ref,
            userId: $request->user()->id
        );

        return redirect()->route('warehouse.raw_materials.index')
            ->with('success', "Stock Opname {$rawMaterial->name} berhasil diperbarui ke {$physicalStock} {$rawMaterial->unit} (Selisih: {$diffText} {$rawMaterial->unit}).");
    }

    /**
     * Kartu Stok Khusus: Riwayat keluar-masuk detail untuk satu bahan baku tertentu.
     */
    public function history(Request $request, RawMaterial $rawMaterial): View
    {
        $query = $rawMaterial->mutations()->with('user');

        if ($type = $request->input('mutation_type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $mutations = $query->paginate(20)->withQueryString();

        // Akumulasi statistik per bahan
        $totalIn = (float) $rawMaterial->mutations()->where('type', 'in')->sum('quantity');
        $totalOut = (float) $rawMaterial->mutations()->where('type', 'out')->sum('quantity');
        $mutationCount = $rawMaterial->mutations()->count();

        $stats = [
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'mutation_count' => $mutationCount,
        ];

        return view('warehouse.raw_materials.history', compact('rawMaterial', 'mutations', 'stats'));
    }

    /**
     * Buku Kas Mutasi Stok Global seluruh bahan baku.
     */
    public function mutations(Request $request): View
    {
        $query = RawMaterialMutation::with(['rawMaterial', 'user'])->latest('id');

        if ($materialId = $request->input('raw_material_id')) {
            if ($materialId !== 'all') {
                $query->where('raw_material_id', $materialId);
            }
        }

        if ($type = $request->input('mutation_type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('rawMaterial', function ($mq) use ($search) {
                        $mq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $mutations = $query->paginate(20)->withQueryString();
        $rawMaterials = RawMaterial::orderBy('name', 'asc')->get(['id', 'code', 'name', 'unit']);

        return view('warehouse.raw_materials.mutations', compact('mutations', 'rawMaterials'));
    }
}
