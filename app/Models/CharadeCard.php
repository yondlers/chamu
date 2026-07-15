<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharadeCard extends Model
{
    use HasFactory;

    protected $table = 'charade_cards';

    protected $fillable = [
        'charade_category_id',
        'subject_id',
        'topic_id',
        'skill_id',
        'word',
        'clue',
        'answer',
        'difficulty',
        'points',
        'time_limit_seconds',
        'is_active',
    ];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(TopicSkill::class, 'skill_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function charadeCategory(): BelongsTo
    {
        return $this->belongsTo(CharadeCategory::class, 'charade_category_id');
    }

    public function charadeSessionCards(): HasMany
    {
        return $this->hasMany(CharadeSessionCard::class, 'charade_card_id');
    }
}
