<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'is_active',
    ];

    // 🔗 Relationship: A brand can have many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function stockReports()
    {
        return $this->hasMany(StockReport::class);
    }
}
