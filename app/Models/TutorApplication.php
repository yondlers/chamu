<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorApplication extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const TOTAL_STEPS = 6;

    protected $table = 'tutor_applications';

    protected $fillable = [
        'user_id',
        'status',
        'tutor_status_id',
        'current_step',
        'profile_image_path',
        'headline',
        'gender',
        'phone',
        'whatsapp',
        'whatsapp_same_as_phone',
        'street',
        'city',
        'province_id',
        'languages',
        'high_school_syllabus',
        'high_school_syllabus_other',
        'attended_university',
        'university_id',
        'university',
        'university_other',
        'graduated',
        'qualification_id',
        'programme',
        'qualification_other',
        'specialization',
        'tutoring_bio',
        'tutoring_experience',
        'tutoring_style',
        'experience_years',
        'teaching_modes',
        'heard_from',
        'heard_from_other',
        'accept_terms',
        'average_rating',
        'reviews_count',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_same_as_phone' => 'boolean',
            'attended_university' => 'boolean',
            'graduated' => 'boolean',
            'accept_terms' => 'boolean',
            'languages' => 'array',
            'teaching_modes' => 'array',
            'submitted_at' => 'datetime',
            'current_step' => 'integer',
            'experience_years' => 'integer',
            'average_rating' => 'decimal:2',
            'reviews_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function tutorStatus(): BelongsTo
    {
        return $this->belongsTo(TutorStatus::class, 'tutor_status_id');
    }

    public function selectedUniversity(): BelongsTo
    {
        return $this->belongsTo(University::class, 'university_id');
    }

    public function selectedQualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class, 'qualification_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(TutorApplicationSubject::class, 'tutor_application_id')->orderBy('sort_order');
    }

    public function marks(): HasMany
    {
        return $this->hasMany(TutorMark::class, 'tutor_application_id')->orderBy('sort_order');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TutorAvailability::class, 'tutor_application_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TutorBooking::class, 'tutor_application_id')->latest('booking_date');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TutorReview::class, 'tutor_application_id')->latest();
    }

    public function visibleReviews(): HasMany
    {
        return $this->reviews()->where('is_visible', true);
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function refreshRatingSummary(): void
    {
        $visibleReviews = $this->visibleReviews();

        $this->forceFill([
            'average_rating' => round((float) $visibleReviews->avg('rating'), 2) ?: null,
            'reviews_count' => (int) $visibleReviews->count(),
        ])->save();
    }
}
