<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fullName',
        'email',
        'phoneNumber',
        'password',
        'business_id',
        'phone',
        'role',
        'status',
        'is_super_user',
        'email_notifications',
        'payment_reminders',
        'marketing_updates',
        'otp_code',
        'otp_expires_at',
        'acceptTerms',
        'working_capital',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'hasBusinessProfile' => 'boolean',
        ];
    }

    /**
     * Get the business profile associated with the user.
     */
    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function borrowers()
    {
        return $this->hasMany(Borrower::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if user has a business profile.
     */
    public function getHasBusinessProfileAttribute(): bool
    {
        return $this->businessProfile()->exists();
    }
}
