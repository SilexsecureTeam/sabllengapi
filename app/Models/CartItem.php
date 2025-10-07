<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'color',
        'customization_id',
    ];

      public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customization()
    {
        return $this->belongsTo(Customization::class);
    }

    /**
     * Boot method to auto-update cart total on save/delete.
     */
    protected static function booted()
    {
        static::saved(function ($item) {
            $item->cart->updateTotal();
        });

        static::deleted(function ($item) {
            $item->cart->updateTotal();
        });
    }
}
