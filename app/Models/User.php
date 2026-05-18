<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable { 
    use HasApiTokens, Notifiable, HasUuids; 
    protected $guarded = []; 
    protected $hidden = ['password', 'remember_token']; 
    protected function casts(): array { 
        return [
            'email_verified_at' => 'datetime', 
            'password' => 'hashed', 
            'is_super_user' => 'boolean',
            'permissions' => 'array'
        ]; 
    } 

    public function business() { return $this->belongsTo(Business::class); } 

    public function hasPermission($permission)
    {
        if ($this->role === 'SUPER_ADMIN') return true;
        
        $perms = $this->permissions ?? $this->getDefaultPermissions();
        return in_array($permission, $perms);
    }

    public function getDefaultPermissions()
    {
        return match ($this->role) {
            'ADMIN' => ['loans.view', 'loans.create', 'loans.edit', 'loans.delete', 'loans.approve', 'disbursements.view', 'disbursements.process', 'customers.view', 'customers.create', 'customers.edit', 'reports.view', 'settings.view', 'settings.edit', 'team.view', 'team.edit'],
            'LOAN_OFFICER' => ['loans.view', 'loans.create', 'loans.edit', 'loans.approve', 'disbursements.view', 'disbursements.process', 'customers.view', 'customers.create', 'customers.edit'],
            'VIEWER' => ['loans.view', 'disbursements.view', 'customers.view', 'reports.view'],
            default => []
        };
    }
}
