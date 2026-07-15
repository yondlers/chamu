<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharadeSession extends Model
{
    use HasFactory;

    protected $table = 'charade_sessions';

    protected $fillable = [
        'user_id',
        'subject_id',
        'topic_id',
        'mode',
        'started_at',
        'completed_at',
        'score',
        'total_cards',
        'correct_cards',
        'time_taken_seconds',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function charadeSessionCards(): HasMany
    {
        return $this->hasMany(CharadeSessionCard::class, 'charade_session_id');
    }
}
