<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;

class RawMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('role', 'admin')->first()
            ?? User::where('role', 'warehouse')->first()
            ?? User::first();

        $adminId = $adminUser?->id ?? 1;

        $materials = [
            [
                'code' => 'BHN-001',
                'name' => 'Biji Kopi Arabika House Blend',
                'category' => 'Biji Kopi',
                'unit' => 'kg',
                'current_stock' => 15.00,
                'min_stock' => 5.00,
                'unit_price' => 185000,
                'notes' => 'Blend Arabika Toraja & Mandheling medium roast untuk espresso base.',
            ],
            [
                'code' => 'BHN-002',
                'name' => 'Biji Kopi Robusta Premium',
                'category' => 'Biji Kopi',
                'unit' => 'kg',
                'current_stock' => 10.00,
                'min_stock' => 4.00,
                'unit_price' => 110000,
                'notes' => 'Robusta Flores dark roast untuk kopi susu gula aren.',
            ],
            [
                'code' => 'BHN-003',
                'name' => 'Fresh Milk Pasteurised',
                'category' => 'Susu & Dairy',
                'unit' => 'liter',
                'current_stock' => 24.00,
                'min_stock' => 8.00,
                'unit_price' => 21000,
                'notes' => 'Susu segar pasteurisasi kemasan 1 liter kualitas barista foam.',
            ],
            [
                'code' => 'BHN-004',
                'name' => 'Susu Evaporasi Tiga Sapi',
                'category' => 'Susu & Dairy',
                'unit' => 'kaleng',
                'current_stock' => 18.00,
                'min_stock' => 6.00,
                'unit_price' => 16500,
                'notes' => 'Susu evaporasi untuk campuran minuman latte dan dessert.',
            ],
            [
                'code' => 'BHN-005',
                'name' => 'Gula Aren Cair Murni',
                'category' => 'Topping & Gula',
                'unit' => 'liter',
                'current_stock' => 12.00,
                'min_stock' => 3.00,
                'unit_price' => 42000,
                'notes' => 'Gula aren organik cair pekat tanpa pengawet.',
            ],
            [
                'code' => 'BHN-006',
                'name' => 'Sirup Karamel Monin 700ml',
                'category' => 'Sirup & Perisa',
                'unit' => 'botol',
                'current_stock' => 6.00,
                'min_stock' => 2.00,
                'unit_price' => 145000,
                'notes' => 'Sirup premium rasa karamel untuk caramel macchiato.',
            ],
            [
                'code' => 'BHN-007',
                'name' => 'Sirup Vanilla Monin 700ml',
                'category' => 'Sirup & Perisa',
                'unit' => 'botol',
                'current_stock' => 5.00,
                'min_stock' => 2.00,
                'unit_price' => 145000,
                'notes' => 'Sirup premium rasa vanila madagascar.',
            ],
            [
                'code' => 'BHN-008',
                'name' => 'Matcha Powder Pure Uji 500g',
                'category' => 'Bubuk & Powder',
                'unit' => 'pack',
                'current_stock' => 4.00,
                'min_stock' => 2.00,
                'unit_price' => 175000,
                'notes' => 'Bubuk teh hijau matcha murni jepang tanpa gula.',
            ],
            [
                'code' => 'BHN-009',
                'name' => 'Cup Kopi Plastik PET 16oz',
                'category' => 'Cup & Packaging',
                'unit' => 'pcs',
                'current_stock' => 500.00,
                'min_stock' => 100.00,
                'unit_price' => 650,
                'notes' => 'Gelas plastik bening tebal 16oz + tutup dome lid.',
            ],
            [
                'code' => 'BHN-010',
                'name' => 'Paper Cup Hot Coffee 8oz',
                'category' => 'Cup & Packaging',
                'unit' => 'pcs',
                'current_stock' => 300.00,
                'min_stock' => 50.00,
                'unit_price' => 550,
                'notes' => 'Gelas kertas panas tahan panas 8oz motif cafe.',
            ],
            [
                'code' => 'BHN-011',
                'name' => 'Sedotan Steril Bungkus Kertas',
                'category' => 'Perlengkapan Bar',
                'unit' => 'pack',
                'current_stock' => 8.00,
                'min_stock' => 2.00,
                'unit_price' => 28000,
                'notes' => 'Sedotan hitam higienis individual paper wrap isi 250 pcs.',
            ],
            [
                'code' => 'BHN-012',
                'name' => 'Kantong Plastik Kresek Takeaway 1 Cup',
                'category' => 'Cup & Packaging',
                'unit' => 'pack',
                'current_stock' => 10.00,
                'min_stock' => 3.00,
                'unit_price' => 14000,
                'notes' => 'Plastik sablon bening isi 100 pcs untuk bawa pulang.',
            ],
        ];

        foreach ($materials as $item) {
            $initialStock = $item['current_stock'];

            $rawMaterial = RawMaterial::firstOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'unit' => $item['unit'],
                    'current_stock' => 0,
                    'min_stock' => $item['min_stock'],
                    'unit_price' => $item['unit_price'],
                    'notes' => $item['notes'],
                ]
            );

            // Jika mutasi perdana belum tercatat, catat mutasi saldo awal
            if ($rawMaterial->mutations()->count() === 0 && $initialStock > 0) {
                $rawMaterial->recordMutation(
                    type: 'in',
                    quantity: $initialStock,
                    notes: 'Saldo awal pendaftaran bahan baku operasional cafe',
                    referenceNumber: 'INITIAL-STOCK',
                    userId: $adminId,
                    unitPrice: $item['unit_price']
                );
            }
        }
    }
}
