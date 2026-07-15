<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteCard extends Model
{
    use HasFactory;

    protected $table = 'note_cards';

    protected $fillable = [
        'note_deck_id',
        'front',
        'back',
        'hint',
        'content_image',
        'sort_order',
    ];

    public function noteDeck(): BelongsTo
    {
        return $this->belongsTo(NoteDeck::class, 'note_deck_id');
    }
}
