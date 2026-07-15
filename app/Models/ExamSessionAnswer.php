<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSessionAnswer extends Model
{
    use HasFactory;

    protected $table = 'exam_session_answers';

    protected $fillable = [
        'exam_session_id',
        'question_id',
        'sub_question_id',
        'selected_answer',
    ];

    public function subQuestion(): BelongsTo
    {
        return $this->belongsTo(SubQuestion::class, 'sub_question_id');
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
