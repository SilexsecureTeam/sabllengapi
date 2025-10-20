<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'order_reference',
        // 'reference',
        'subtotal',
        'delivery_fee',
        'total',
        'coupon_code',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'shipping_address',
        'payment_method',
        'status',
    ];

    /**
     * Relationship: Order has many items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relationship: Order belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate total dynamically if needed
     */
    public function calculateTotal()
    {
        return ($this->subtotal - $this->discount_amount) + $this->delivery_fee + $this->tax_amount;
    }
}
