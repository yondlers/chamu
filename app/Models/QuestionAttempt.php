<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionAttempt extends Model
{
    use HasFactory;

    protected $table = 'question_attempts';

    protected $fillable = [
        'user_id',
        'question_id',
        'sub_question_id',
        'past_paper_question_id',
        'exam_session_id',
        'selected_answer',
        'correct_answer',
        'is_correct',
        'marks_awarded',
        'time_taken_seconds',
        'attempt_number',
        'answered_at',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function pastPaperQuestion(): BelongsTo
    {
        return $this->belongsTo(PastPaperQuestion::class, 'past_paper_question_id');
    }

    public function subQuestion(): BelongsTo
    {
        return $this->belongsTo(SubQuestion::class, 'sub_question_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
