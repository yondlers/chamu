<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    use HasFactory;

    protected $table = 'social_posts';

    protected $fillable = [
        'user_id',
        'platform',
        'title',
        'message',
        'audience',
        'link_url',
        'media_url',
        'status',
        'external_post_id',
        'external_permalink',
        'request_payload',
        'response_payload',
        'error_message',
        'scheduled_at',
        'published_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SocialPostResponse::class, 'social_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusLabel(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }
}
