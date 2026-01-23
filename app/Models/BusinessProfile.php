<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'businessName',
        'logo_url',
        'tagline',
        'primary_color',
        'secondary_color',
        'currency_code',
        'locale',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the business profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
