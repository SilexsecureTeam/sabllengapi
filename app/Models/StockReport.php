<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReport extends Model
{
   use HasFactory;

    protected $fillable = [
        'name',
        'barcode',
        'brand_id',
        'supplier_id',
        'order_code',
        'category_id',
        'current_stock',
        'total_stock',
        'on_order',
        'cost_price',
        'sale_price',
        'total_cost',
        'total_value',
        'margin',
        'margin_perc',
        'measure',
        'unit_of_sale',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'margin' => 'decimal:2',
        'margin_perc' => 'decimal:2',
        'current_stock' => 'integer',
        'total_stock' => 'integer',
        'on_order' => 'integer',
    ];

    // 🔗 Relationships
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
