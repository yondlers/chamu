<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityAdmissionRule extends Model
{
    use HasFactory;

    protected $table = 'university_admission_rules';

    protected $fillable = [
        'admission_rule_id',
        'university_id',
        'faculty_id',
        'qualification_id',
        'grade_id',
        'priority',
        'is_default',
        'overrides',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'overrides' => 'array',
    ];

    public function admissionRule(): BelongsTo
    {
        return $this->belongsTo(AdmissionRule::class);
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }
}
