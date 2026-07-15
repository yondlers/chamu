<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSessionQuestion extends Model
{
    use HasFactory;

    protected $table = 'exam_session_questions';

    protected $fillable = [
        'exam_session_id',
        'question_id',
        'past_paper_question_id',
        'question_order',
        'marks',
        'selected_answer',
        'is_correct',
        'time_taken_seconds',
    ];

    public function pastPaperQuestion(): BelongsTo
    {
        return $this->belongsTo(PastPaperQuestion::class, 'past_paper_question_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}
