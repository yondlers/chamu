<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PastPaperQuestion extends Model
{
    use HasFactory;

    protected $table = 'past_paper_questions';

    protected $fillable = [
        'past_paper_id',
        'skill_id',
        'topic_id',
        'subject_id',
        'paper_id',
        'province_id',
        'question_number',
        'question',
        'hint',
        'answer',
        'options',
        'question_type',
        'answer_type',
        'marks',
        'difficulty',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class, 'paper_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(TopicSkill::class, 'skill_id');
    }

    public function pastPaper(): BelongsTo
    {
        return $this->belongsTo(PastPaper::class, 'past_paper_id');
    }

    public function aiExplanations(): HasMany
    {
        return $this->hasMany(AiExplanation::class, 'past_paper_question_id');
    }

    public function examSessionQuestions(): HasMany
    {
        return $this->hasMany(ExamSessionQuestion::class, 'past_paper_question_id');
    }

    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class, 'past_paper_question_id');
    }
}
