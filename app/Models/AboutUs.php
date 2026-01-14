<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    protected $table = 'about_us';

    protected $fillable = [
        'heading',
        'content',
        'founder_name',
        'founder_title',
        'founder_image',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
