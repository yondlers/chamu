<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNoteCard extends Model
{
    use HasFactory;

    protected $table = 'user_note_cards';

    protected $fillable = [
        'user_note_deck_id',
        'front',
        'back',
        'hint',
        'sort_order',
    ];

    public function userNoteDeck(): BelongsTo
    {
        return $this->belongsTo(UserNoteDeck::class, 'user_note_deck_id');
    }
}
