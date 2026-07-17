<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BursaryApplication extends Model
{
    use HasFactory;

    protected $table = 'bursary_applications';

    protected $fillable = [
        'user_id',
        'bursary_id',
        'status',
        'delivery_type',
        'provider_email',
        'provider_postal_address',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'applicant_postal_address',
        'study_level',
        'institution',
        'qualification',
        'current_year',
        'funding_need',
        'household_income',
        'sassa_recipient',
        'special_circumstances',
        'metadata',
        'submitted_at',
        'receipt_sent_at',
    ];

    protected $casts = [
        'sassa_recipient' => 'boolean',
        'special_circumstances' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'receipt_sent_at' => 'datetime',
    ];

    public function bursary(): BelongsTo
    {
        return $this->belongsTo(Bursary::class, 'bursary_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BursaryApplicationDocument::class, 'bursary_application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
