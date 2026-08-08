<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorBookingStatus extends Model
{
    use HasFactory;

    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const CANCELLED = 'cancelled';

    public const COMPLETED = 'completed';

    protected $table = 'tutor_booking_statuses';

    protected $fillable = [
        'name',
        'description',
    ];

    public function tutorBookings(): HasMany
    {
        return $this->hasMany(TutorBooking::class, 'tutor_booking_status_id');
    }
}
