<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'current_stock',
        'min_stock',
        'unit_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke riwayat kartu stok mutasi bahan baku.
     */
    public function mutations(): HasMany
    {
        return $this->hasMany(RawMaterialMutation::class)->latest('id');
    }

    /**
     * Relasi ke transaksi pengeluaran belanja bahan baku.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Cek apakah stok habis (<= 0).
     */
    public function isOutOfStock(): bool
    {
        return (float) $this->current_stock <= 0;
    }

    /**
     * Cek apakah stok menipis (di bawah atau sama dengan batas minimum).
     */
    public function isLowStock(): bool
    {
        $current = (float) $this->current_stock;
        $min = (float) $this->min_stock;

        return $current > 0 && $current <= $min;
    }

    /**
     * Cek apakah stok dalam kondisi aman.
     */
    public function isSafeStock(): bool
    {
        return ! $this->isOutOfStock() && ! $this->isLowStock();
    }

    /**
     * Total nilai persediaan rupiah aset bahan di bar/gudang.
     */
    public function totalAssetValue(): float
    {
        return (float) ($this->current_stock * $this->unit_price);
    }

    /**
     * Label status stok (Aman / Menipis / Habis).
     */
    public function stockStatusLabel(): string
    {
        if ($this->isOutOfStock()) {
            return 'Habis';
        }

        if ($this->isLowStock()) {
            return 'Menipis';
        }

        return 'Aman';
    }

    /**
     * Mencatat mutasi keluar-masuk barang secara transparan dan mengupdate sisa stok fisik.
     *
     * @param  string  $type  ('in', 'out', 'adjustment')
     * @param  float  $quantity  (Untuk in/out: jumlah mutasi. Untuk adjustment: stok fisik nyata terbaru)
     */
    public function recordMutation(
        string $type,
        float $quantity,
        ?string $notes = null,
        ?string $referenceNumber = null,
        ?int $userId = null,
        ?float $unitPrice = null
    ): RawMaterialMutation {
        $stockBefore = (float) $this->current_stock;
        $mutationQty = abs($quantity);

        if ($type === 'in') {
            $stockAfter = $stockBefore + $mutationQty;
            if ($unitPrice !== null && $unitPrice > 0) {
                $this->unit_price = $unitPrice;
            }
        } elseif ($type === 'out') {
            $stockAfter = max(0, $stockBefore - $mutationQty);
        } elseif ($type === 'adjustment') {
            // Untuk adjustment (opname): parameter quantity adalah hitungan fisik akhir
            $stockAfter = (float) $quantity;
            $mutationQty = abs($stockAfter - $stockBefore);
        } else {
            throw new \InvalidArgumentException("Jenis mutasi tidak valid: {$type}");
        }

        $this->current_stock = $stockAfter;
        $this->save();

        return $this->mutations()->create([
            'type' => $type,
            'quantity' => $mutationQty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
            'user_id' => $userId ?? auth()->id() ?? User::where('role', 'admin')->value('id') ?? 1,
        ]);
    }

    /**
     * Menghasilkan kode bahan otomatis unik (BHN-001, BHN-002, dst).
     */
    public static function generateCode(): string
    {
        $count = static::count() + 1;
        $code = sprintf('BHN-%03d', $count);

        while (static::where('code', $code)->exists()) {
            $count++;
            $code = sprintf('BHN-%03d', $count);
        }

        return $code;
    }
}
