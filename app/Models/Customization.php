<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customization extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'image_path',
        'text',
        'instruction',
        'position',
        'coordinates',
    ];

    protected $casts = [
        'coordinates' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
