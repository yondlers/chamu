<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualificationAdmissionScoreVariant extends Model
{
    use HasFactory;

    protected $table = 'qualification_admission_score_variants';

    protected $fillable = [
        'qualification_id',
        'subject_id',
        'subject_name',
        'minimum_mark',
        'aps_level_required',
        'admission_score_required',
        'label',
        'notes',
    ];

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class, 'qualification_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
