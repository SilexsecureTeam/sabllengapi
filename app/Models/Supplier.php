<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'contact_person',
        'is_active',
    ];

    // 🔗 Relationships

    // A supplier can supply many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // A supplier can appear in many stock reports
    public function stockReports()
    {
        return $this->hasMany(StockReport::class);
    }
}
