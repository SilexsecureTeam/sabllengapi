<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'email',
        'phone',
        'contact_number2',
        'address',
        'address_line2',
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
