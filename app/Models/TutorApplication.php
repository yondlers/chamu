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

    public const TOTAL_STEPS = 4;

    protected $table = 'tutor_applications';

    protected $fillable = [
        'user_id',
        'status',
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
        'attended_university',
        'graduated',
        'university',
        'programme',
        'specialization',
        'tutoring_bio',
        'tutoring_experience',
        'tutoring_style',
        'experience_years',
        'teaching_modes',
        'heard_from',
        'accept_terms',
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

    public function subjects(): HasMany
    {
        return $this->hasMany(TutorApplicationSubject::class, 'tutor_application_id')->orderBy('sort_order');
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
