<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_name',
        'code',
        'type',
        'value',
        'start_date',
        'expires_at',
        'usage_limit',
        'times_used',
        'is_active'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    /**
     * Check if coupon is valid (not expired and within usage limit)
     */
    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_product');
    }
}
