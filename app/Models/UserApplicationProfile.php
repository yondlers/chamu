<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserApplicationProfile extends Model
{
    use HasFactory;

    protected $table = 'user_application_profiles';

    protected $fillable = [
        'user_id',
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
    ];

    protected $casts = [
        'sassa_recipient' => 'boolean',
        'special_circumstances' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
