<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $table = 'password_resets';

    protected $fillable = [
        'email',
        'token',
        'expires_at',
    ];

    public $timestamps = true;

    // Helper to check if token is expired
    public function isExpired()
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }
}
