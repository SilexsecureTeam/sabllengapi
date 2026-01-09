<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'subcategory_id',
        'brand_id',
        'supplier_id',
        'coupon_id',
        'name',
        'images',
        'description',
        'cost_price',
        'tax_rate',
        'cost_inc_tax',
        'sale_price',
        'sale_price_inc_tax',
        'is_variable_price',
        'margin_perc',
        'tax_exempt_eligible',
        'rr_price',
        'bottle_deposit_item_name',
        'barcode',
        'size',
        'colours',
        'product_code',
        'customize',
        'age_restriction',
    ];

    protected $casts = [
        'size' => 'array',
        'colours' => 'array',
        'images' => 'array',
        'customize' => 'boolean',
        'is_variable_price' => 'boolean',
        'tax_exempt_eligible' => 'boolean',
        'cost_price' => 'decimal:2',
        'cost_inc_tax' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_price_inc_tax' => 'decimal:2',
        'rr_price' => 'decimal:2',
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // public function images()
    // {
    //     return $this->hasMany(ProductImage::class);
    // }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customization()
    {
        return $this->hasMany(Customization::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function eposnowSyncLogs()
    {
        return $this->hasMany(EposnowSyncLog::class);
    }


    //  public function getImagesAttribute($value)
    // {
    //     if (!is_array($value)) {
    //         return [];
    //     }

    //     return collect($value)->map(function ($img) {
    //         return [
    //             'id'   => $img['id'] ?? null,
    //             'path' => $img['path'] ?? null,
    //             'url'  => isset($img['path'])
    //                 ? asset('storage/' . $img['path'])
    //                 : null,
    //         ];
    //     })->values()->toArray();
    // }
}
