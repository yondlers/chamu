<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopicSkill extends Model
{
    use HasFactory;

    protected $table = 'topic_skills';

    protected $fillable = [
        'topic_id',
        'name',
        'description',
        'image',
        'sort_order',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function charadeCards(): HasMany
    {
        return $this->hasMany(CharadeCard::class, 'skill_id');
    }

    public function pastPaperQuestions(): HasMany
    {
        return $this->hasMany(PastPaperQuestion::class, 'skill_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'skill_id');
    }
}
