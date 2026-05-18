<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BusinessConfiguration extends Model
{
    use HasUuids;

    protected $guarded = [];
    protected $casts = [
        'settings' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
