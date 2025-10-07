<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image'
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
    
    //GET /api/categories → list categories
    //POST /api/categories → create category
    //GET /api/categories/{id} → show category
    //PUT /api/categories/{id} → update category
    //DELETE /api/categories/{id} → delete category
}
