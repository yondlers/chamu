<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BursarySubjectRequirement extends Model
{
    use HasFactory;

    protected $table = 'bursary_subject_requirements';

    protected $fillable = [
        'bursary_id',
        'subject_id',
        'grade_id',
        'subject_name',
        'minimum_mark',
        'aps_level_required',
        'requirement_type',
        'requirement_group',
        'notes',
    ];

    public function bursary(): BelongsTo
    {
        return $this->belongsTo(Bursary::class, 'bursary_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
