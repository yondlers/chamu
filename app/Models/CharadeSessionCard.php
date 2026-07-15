<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharadeSessionCard extends Model
{
    use HasFactory;

    protected $table = 'charade_session_cards';

    protected $fillable = [
        'charade_session_id',
        'charade_card_id',
        'card_order',
        'guessed_answer',
        'is_correct',
        'points_awarded',
        'time_taken_seconds',
    ];

    public function charadeCard(): BelongsTo
    {
        return $this->belongsTo(CharadeCard::class, 'charade_card_id');
    }

    public function charadeSession(): BelongsTo
    {
        return $this->belongsTo(CharadeSession::class, 'charade_session_id');
    }
}
