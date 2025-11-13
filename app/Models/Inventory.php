<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'name',
        'barcode',
        'brand',
        'supplier',
        'order_code',
        'category_name',
        'current_stock',
        'total_stock',
        'on_order',
        'cost_price',
        'sales_price',
        'total_cost',
        'total_value',
        'margin',
        'margin_percentage',
        'measure',
        'unit_of_sale'
    ];

      protected static function boot()
    {
        parent::boot();

        static::creating(function ($inventory) {
            if (empty($inventory->barcode)) {
                $inventory->barcode = self::generateEAN13Barcode();
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
