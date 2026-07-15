<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

    protected $table = 'exam_sessions';

    protected $fillable = [
        'user_id',
        'subject_id',
        'curriculum_id',
        'title',
        'mode',
        'paper_type',
        'quiz_type',
        'source',
        'started_at',
        'completed_at',
        'time_limit_minutes',
        'score',
        'total_marks',
        'percentage',
        'show_answers_immediately',
        'randomize_questions',
        'randomize_options',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function examSessionAnswers(): HasMany
    {
        return $this->hasMany(ExamSessionAnswer::class, 'exam_session_id');
    }

    public function examSessionQuestions(): HasMany
    {
        return $this->hasMany(ExamSessionQuestion::class, 'exam_session_id');
    }

    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class, 'exam_session_id');
    }
}
