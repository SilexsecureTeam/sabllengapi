<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'supplier_id',
        'name',
        'images',
        'description',
        'cost_price',
        'tax_rate',
        'cost_inc_tax',
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
        'age_restriction',
    ];

    protected $casts = [
        'size' => 'array',
        'colours' => 'array',
        'images' => 'array',
        'is_variable_price' => 'boolean',
        'tax_exempt_eligible' => 'boolean',
        'cost_price' => 'decimal:2',
        'cost_inc_tax' => 'decimal:2',
        'sale_price_inc_tax' => 'decimal:2',
        'rr_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
