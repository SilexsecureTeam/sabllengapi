<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustedOrganization extends Model
{
    protected $fillable = [
        'heading',
        'logos',
        'is_active'
    ];

    protected $casts = [
        'logos' => 'array',
        'is_active' => 'boolean'
    ];
}
