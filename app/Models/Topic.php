<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'topics';

    protected $fillable = [
        'grade_id',
        'term_id',
        'subject_id',
        'paper_id',
        'name',
        'image',
        'sort_order',
        'weighting_percentage',
        'weighting_marks',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class, 'paper_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function aiExplanations(): HasMany
    {
        return $this->hasMany(AiExplanation::class, 'topic_id');
    }

    public function charadeCards(): HasMany
    {
        return $this->hasMany(CharadeCard::class, 'topic_id');
    }

    public function charadeCategories(): HasMany
    {
        return $this->hasMany(CharadeCategory::class, 'topic_id');
    }

    public function charadeSessions(): HasMany
    {
        return $this->hasMany(CharadeSession::class, 'topic_id');
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'topic_id');
    }

    public function noteDecks(): HasMany
    {
        return $this->hasMany(NoteDeck::class, 'topic_id');
    }

    public function pastPaperQuestions(): HasMany
    {
        return $this->hasMany(PastPaperQuestion::class, 'topic_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'topic_id');
    }

    public function topicContents(): HasMany
    {
        return $this->hasMany(TopicContent::class, 'topic_id');
    }

    public function topicSkills(): HasMany
    {
        return $this->hasMany(TopicSkill::class, 'topic_id');
    }

    public function userNoteDecks(): HasMany
    {
        return $this->hasMany(UserNoteDeck::class, 'topic_id');
    }
}
