<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_id',
        'document_type',
        'file_path',
        'original_name',
        'expiry_date',
        'verification_status',
        'rejection_reason',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function borrower()
    {
        return $this->belongsTo(Borrower::class);
    }
}
