<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    protected $table = 'blms_businesses';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','business_name','registration_number','email','phone','address','city','country','industry','is_active'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) { $model->id = (string) Str::uuid(); }
        });
    }

    public function users() { return $this->hasMany(User::class); }
}
