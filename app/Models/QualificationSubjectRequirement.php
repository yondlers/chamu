<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualificationSubjectRequirement extends Model
{
    use HasFactory;

    protected $table = 'qualification_subject_requirements';

    protected $fillable = [
        'qualification_id',
        'subject_id',
        'grade_id',
        'subject_name',
        'minimum_mark',
        'aps_level_required',
        'requirement_type',
        'requirement_group',
        'notes',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class, 'qualification_id');
    }
}
