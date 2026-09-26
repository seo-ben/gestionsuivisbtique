<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    public $timestamps = false;
    protected $table = 'sync_logs';

    protected $fillable = ['table_name', 'record_uuid', 'vendeur_id', 'device_id', 'action', 'synced_at'];

    protected $casts = ['synced_at' => 'datetime'];
}
