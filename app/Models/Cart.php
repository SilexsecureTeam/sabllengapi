<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'total',
    ];

     //A cart belongs to a user (nullable for guests).

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Recalculate and update the total in DB.
     */
    public function updateTotal()
    {
        $total = $this->items->sum(fn ($item) => $item->price * $item->quantity);
        $this->updateQuietly(['total' => $total]); // prevents recursion
    }
}
