<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
     use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'expires_at',
        'usage_limit',
        'times_used',
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
}
