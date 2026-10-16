<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Product
 * ------------------------------------------------------------------
 * Katalog produk ACICU. Sumber tunggal untuk homepage dan panel admin
 * (lihat migrasi 2026_10_16_000001_create_products_table.php).
 */
class Product extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'name',
        'category',
        'description',
        'price',
        'image',
        'stock',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * URL gambar yang siap dipakai pada atribut src.
     */
    public function imageUrl(): string
    {
        $image = (string) $this->image;

        if ($image === '') {
            return '';
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return asset($image);
    }

    /**
     * Harga terformat, mis. "Rp10.000".
     */
    public function formattedPrice(): string
    {
        return 'Rp'.number_format($this->price, 0, ',', '.');
    }
}
