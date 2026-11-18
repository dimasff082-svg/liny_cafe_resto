<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'type',
        'mutation_type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_number',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
        ];
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor alias untuk mutation_type -> type.
     */
    public function getMutationTypeAttribute(): ?string
    {
        return $this->attributes['type'] ?? $this->attributes['mutation_type'] ?? null;
    }

    /**
     * Mutator alias untuk mutation_type -> type.
     */
    public function setMutationTypeAttribute($value): void
    {
        $this->attributes['type'] = $value;
    }

    /**
     * Label jenis mutasi kartu stok.
     */
    public function typeLabel(): string
    {
        $type = $this->type ?? $this->mutation_type;

        return match ($type) {
            'in' => 'Masuk / Restock',
            'out' => 'Keluar / Pemakaian Bar & Dapur',
            'adjustment' => 'Penyesuaian / Opname',
            default => ucfirst((string) $type),
        };
    }

    /**
     * Tanda (+/-) perubahan stok.
     */
    public function quantitySign(): string
    {
        $type = $this->type ?? $this->mutation_type;

        if ($type === 'in') {
            return '+';
        }

        if ($type === 'out') {
            return '-';
        }

        // Untuk adjustment
        return (float) $this->stock_after >= (float) $this->stock_before ? '+' : '-';
    }
}
