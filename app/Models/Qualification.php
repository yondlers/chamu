<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Qualification extends Model
{
    use HasFactory;

    protected $table = 'qualifications';

    protected $fillable = [
        'university_id',
        'faculty_id',
        'qualification_type_id',
        'nqf_level_id',
        'required_grade_id',
        'name',
        'abbreviation',
        'duration_years',
        'aps_required',
        'aggregate_average_required',
        'admission_score_required',
        'minimum_pass_type',
        'closing_month',
        'closing_day',
        'is_selection_programme',
        'notes',
        'source_url',
    ];

    public function qualificationType(): BelongsTo
    {
        return $this->belongsTo(QualificationType::class, 'qualification_type_id');
    }

    public function nqfLevel(): BelongsTo
    {
        return $this->belongsTo(NqfLevel::class, 'nqf_level_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'university_id');
    }

    public function qualificationSubjectRequirements(): HasMany
    {
        return $this->hasMany(QualificationSubjectRequirement::class, 'qualification_id');
    }

    public function admissionScoreVariants(): HasMany
    {
        return $this->hasMany(QualificationAdmissionScoreVariant::class, 'qualification_id');
    }
}
