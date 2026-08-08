<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'chats';

    protected $fillable = [
        'user_id',
        'guest_token',
        'ip_address',
        'user_agent',
        'device_type',
        'title',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_id');
    }

    public function participantLabel(): string
    {
        if ($this->user !== null && filled($this->user->name)) {
            return $this->user->name;
        }

        if ($this->user_id !== null) {
            return 'User #'.$this->user_id;
        }

        return $this->ip_address ?: 'Guest visitor';
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }
}
