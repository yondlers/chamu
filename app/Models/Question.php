<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions';

    protected $fillable = [
        'skill_id',
        'topic_id',
        'subject_id',
        'paper_id',
        'province_id',
        'answer_id',
        'question_number',
        'title',
        'instructions',
        'image',
        'hint',
        'source',
        'difficulty',
        'sort_order',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class, 'answer_id');
    }

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

    public function aiExplanations(): HasMany
    {
        return $this->hasMany(AiExplanation::class, 'question_id');
    }

    public function examSessionAnswers(): HasMany
    {
        return $this->hasMany(ExamSessionAnswer::class, 'question_id');
    }

    public function examSessionQuestions(): HasMany
    {
        return $this->hasMany(ExamSessionQuestion::class, 'question_id');
    }

    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class, 'question_id');
    }

    public function subQuestions(): HasMany
    {
        return $this->hasMany(SubQuestion::class, 'question_id');
    }
}
