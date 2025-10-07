<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'age',
        'salary',
        'working_hours_start',
        'working_hours_end',
        'staff_role',
        'staff_address',
        'additional_information',
        'photo',
    ];

    protected $casts = [
        'salary' => 'float',
    ];
}
