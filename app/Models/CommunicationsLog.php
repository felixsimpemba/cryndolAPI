<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommunicationsLog extends Model
{
    use HasUuids;
    
    // Explicitly define the table name because Laravel pluralizes to 'communications_logs' which mismatches 'communications_log'
    protected $table = 'communications_log';

    protected $guarded = [];
    protected $casts = [
        'gateway_response' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
