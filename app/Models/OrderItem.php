<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'customization_id',
        'color',
        'quantity',
        'price',
    ];

    /**
     * Relationship: OrderItem belongs to an order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: OrderItem belongs to a product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: OrderItem may belong to a customization
     */
    public function customization()
    {
        return $this->belongsTo(Customization::class);
    }

    /**
     * Calculate total price for this item
     */
    public function totalPrice()
    {
        return $this->quantity * $this->price;
    }
}
