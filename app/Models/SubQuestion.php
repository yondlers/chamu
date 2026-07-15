<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubQuestion extends Model
{
    use HasFactory;

    protected $table = 'sub_questions';

    protected $fillable = [
        'question_id',
        'answer_id',
        'sub_question_number',
        'question',
        'hint',
        'question_type',
        'answer_type',
        'options',
        'sort_order',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class, 'answer_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function examSessionAnswers(): HasMany
    {
        return $this->hasMany(ExamSessionAnswer::class, 'sub_question_id');
    }

    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class, 'sub_question_id');
    }
}
