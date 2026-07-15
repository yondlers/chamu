<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PastPaper extends Model
{
    use HasFactory;

    protected $table = 'past_papers';

    protected $fillable = [
        'subject_id',
        'exam_body',
        'paper_number',
        'image',
        'year',
        'session',
        'source_url',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function pastPaperQuestions(): HasMany
    {
        return $this->hasMany(PastPaperQuestion::class, 'past_paper_id');
    }
}
