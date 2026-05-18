<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasUuids;

    protected $guarded = [];
    protected $casts = [
        'payload' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
