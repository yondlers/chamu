<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorAvailability extends Model
{
    use HasFactory;

    protected $table = 'tutor_availabilities';

    protected $fillable = [
        'tutor_application_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function tutorApplication(): BelongsTo
    {
        return $this->belongsTo(TutorApplication::class, 'tutor_application_id');
    }

    public function tutorBookings(): HasMany
    {
        return $this->hasMany(TutorBooking::class, 'tutor_availability_id');
    }
}
