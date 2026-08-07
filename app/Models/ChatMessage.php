<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'provider',
        'model',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    public function providerLabel(): ?string
    {
        if ($this->role !== 'assistant' || blank($this->provider) || $this->provider === 'system') {
            return null;
        }

        return app(\App\Services\LemoAi\LemoAiRouter::class)->labelFor($this->provider, $this->model);
    }
}
