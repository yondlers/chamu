<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $table = 'schools';

    protected $fillable = [
        'curriculum_id',
        'province_id',
        'name',
        'province',
        'district',
        'emis_number',
        'logo',
        'website',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'school_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'school_id');
    }
}
