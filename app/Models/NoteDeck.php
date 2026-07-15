<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteDeck extends Model
{
    use HasFactory;

    protected $table = 'note_decks';

    protected $fillable = [
        'topic_id',
        'subject_id',
        'title',
        'description',
        'content_image',
        'is_admin_template',
        'is_public',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function noteCards(): HasMany
    {
        return $this->hasMany(NoteCard::class, 'note_deck_id');
    }
}
