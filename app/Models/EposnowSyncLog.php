<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EposnowSyncLog extends Model
{
    protected $table = 'eposnow_sync_log';

    protected $fillable = [
        'order_id',
        'product_id',
        'sync_type',
        'status',
        'response',
        'error_message',
        'synced_at'
    ];

    protected $casts = [
        'response' => 'array',
        'synced_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
