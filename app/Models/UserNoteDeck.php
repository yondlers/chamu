<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserNoteDeck extends Model
{
    use HasFactory;

    protected $table = 'user_note_decks';

    protected $fillable = [
        'user_id',
        'subject_id',
        'topic_id',
        'title',
        'description',
        'is_public',
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

    public function userNoteCards(): HasMany
    {
        return $this->hasMany(UserNoteCard::class, 'user_note_deck_id');
    }
}
