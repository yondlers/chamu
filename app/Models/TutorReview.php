<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorReview extends Model
{
    use HasFactory;

    protected $table = 'tutor_reviews';

    protected $fillable = [
        'tutor_application_id',
        'reviewer_user_id',
        'tutor_booking_id',
        'rating',
        'comment',
        'is_visible',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_visible' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    public function tutorApplication(): BelongsTo
    {
        return $this->belongsTo(TutorApplication::class, 'tutor_application_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function tutorBooking(): BelongsTo
    {
        return $this->belongsTo(TutorBooking::class, 'tutor_booking_id');
    }
}
