<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExplanation extends Model
{
    use HasFactory;

    protected $table = 'ai_explanations';

    protected $fillable = [
        'question_id',
        'past_paper_question_id',
        'topic_id',
        'title',
        'explanation',
        'worked_solution',
        'common_mistakes',
        'memory_tip',
        'video_url',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function pastPaperQuestion(): BelongsTo
    {
        return $this->belongsTo(PastPaperQuestion::class, 'past_paper_question_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
