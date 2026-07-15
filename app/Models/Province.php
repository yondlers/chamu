<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasFactory;

    protected $table = 'provinces';

    protected $fillable = [
        'country_id',
        'name',
        'code',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function pastPaperQuestions(): HasMany
    {
        return $this->hasMany(PastPaperQuestion::class, 'province_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'province_id');
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'province_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'province_id');
    }
}
