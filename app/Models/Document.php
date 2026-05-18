<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Document extends Model { 
    use HasUuids; 
    protected $guarded = []; 
    const UPDATED_AT = null;
}
