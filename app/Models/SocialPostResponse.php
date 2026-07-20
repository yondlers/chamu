<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostResponse extends Model
{
    use HasFactory;

    protected $table = 'social_post_responses';

    protected $fillable = [
        'social_post_id',
        'platform',
        'response_type',
        'external_response_id',
        'author_name',
        'author_handle',
        'body',
        'request_payload',
        'response_payload',
        'received_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'received_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }
}
