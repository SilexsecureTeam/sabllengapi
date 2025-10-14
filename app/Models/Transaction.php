<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'reference',
        'currency',
        'amount',
        'status',
        'payment_channel',
        'gateway_response',
        'paid_at',
        'authorization_code',
        'customer_email',
        'transaction_data',
    ];

    protected $casts = [
        'transaction_data' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * The user who made the payment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The order that this transaction is for
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
