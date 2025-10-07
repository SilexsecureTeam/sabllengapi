<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory  ;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discounted_price',
        'tax_included',
        'expiration_start',
        'expiration_end',
        'stock_quantity',
        'unlimited',
        'stock_status',
        'customize',
        'colors',
        'images',
    ];

    protected $casts = [
        'tax_included' => 'boolean',
        'unlimited' => 'boolean',
        'customize' => 'boolean',
        'colors' => 'array',  // auto-cast JSON to array
        'images' => 'array',  // auto-cast JSON to array
    ];

    // Example relations if you already have categories/tags pivot tables
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
