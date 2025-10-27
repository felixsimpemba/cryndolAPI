<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlmsCustomer extends Model
{
    use HasFactory;

    protected $table = 'blms_customers';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','business_id','first_name','last_name','email','phone','id_number','id_type','date_of_birth','address','city','country','occupation','annual_income','credit_score','status','created_by'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'annual_income' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) { if (!$model->id) { $model->id = (string) Str::uuid(); } });
    }

    public function business() { return $this->belongsTo(Business::class); }
}
