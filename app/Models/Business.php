<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Business extends Model { 
    use HasUuids; 
    protected $guarded = []; 
    public function users() { return $this->hasMany(User::class); } 
    public function customers() { return $this->hasMany(Customer::class); } 
    public function loans() { return $this->hasMany(Loan::class); } 
}
