<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    use HasFactory;

    protected $table = 'site_visits';

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'method',
        'url',
        'route_name',
        'referrer',
        'user_agent',
        'device_type',
        'platform',
        'browser',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
