<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TutorBooking extends Model
{
    use HasFactory;

    protected $table = 'tutor_bookings';

    protected $fillable = [
        'tutor_application_id',
        'learner_user_id',
        'subject_id',
        'subject_other',
        'tutor_availability_id',
        'tutor_booking_status_id',
        'booking_date',
        'start_time',
        'end_time',
        'notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tutorApplication(): BelongsTo
    {
        return $this->belongsTo(TutorApplication::class, 'tutor_application_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function tutorAvailability(): BelongsTo
    {
        return $this->belongsTo(TutorAvailability::class, 'tutor_availability_id');
    }

    public function tutorBookingStatus(): BelongsTo
    {
        return $this->belongsTo(TutorBookingStatus::class, 'tutor_booking_status_id');
    }

    public function tutorReview(): HasOne
    {
        return $this->hasOne(TutorReview::class, 'tutor_booking_id');
    }
}
