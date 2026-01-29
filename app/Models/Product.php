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
        'sales_price',
        'sales_price_inc_tax',
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

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

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
        return $this->belongsToMany(Coupon::class);
    }

    public function eposnowSyncLogs()
    {
        return $this->hasMany(EposnowSyncLog::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->barcode)) {
                $product->barcode = self::generateEAN13Barcode();
            }
        });
    }

    /**
     * Generate a random valid 13-digit EAN-13 barcode.
     */
    private static function generateEAN13Barcode()
    {
        // Generate first 12 random digits
        $barcode = '';
        for ($i = 0; $i < 12; $i++) {
            $barcode .= mt_rand(0, 9);
        }

        // Compute checksum (13th digit)
        $checksum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode[$i];
            $checksum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $checksum = (10 - ($checksum % 10)) % 10;

        return $barcode . $checksum;
    }
}
