<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';

    protected $fillable = [
        'curriculum_id',
        'grade_id',
        'subject_category_id',
        'name',
        'code',
        'abbreviation',
        'colour',
        'icon',
        'sort_order',
        'is_live',
    ];

    public function subjectCategory(): BelongsTo
    {
        return $this->belongsTo(SubjectCategory::class, 'subject_category_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function charadeCards(): HasMany
    {
        return $this->hasMany(CharadeCard::class, 'subject_id');
    }

    public function charadeCategories(): HasMany
    {
        return $this->hasMany(CharadeCategory::class, 'subject_id');
    }

    public function charadeSessions(): HasMany
    {
        return $this->hasMany(CharadeSession::class, 'subject_id');
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'subject_id');
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'subject_id');
    }

    public function noteDecks(): HasMany
    {
        return $this->hasMany(NoteDeck::class, 'subject_id');
    }

    public function pastPaperQuestions(): HasMany
    {
        return $this->hasMany(PastPaperQuestion::class, 'subject_id');
    }

    public function pastPapers(): HasMany
    {
        return $this->hasMany(PastPaper::class, 'subject_id');
    }

    public function qualificationSubjectRequirements(): HasMany
    {
        return $this->hasMany(QualificationSubjectRequirement::class, 'subject_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'subject_id');
    }

    public function topicContents(): HasMany
    {
        return $this->hasMany(TopicContent::class, 'subject_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'subject_id');
    }

    public function userNoteDecks(): HasMany
    {
        return $this->hasMany(UserNoteDeck::class, 'subject_id');
    }

    public function userSubjectPreferences(): HasMany
    {
        return $this->hasMany(UserSubjectPreference::class, 'subject_id');
    }

    public function userSubjectResults(): HasMany
    {
        return $this->hasMany(UserSubjectResult::class, 'subject_id');
    }
}
