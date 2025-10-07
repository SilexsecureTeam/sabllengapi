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
    
}
