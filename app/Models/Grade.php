<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use HasFactory;

    protected $table = 'grades';

    protected $fillable = [
        'curriculum_id',
        'nqf_level_id',
        'name',
        'sort_order',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function nqfLevel(): BelongsTo
    {
        return $this->belongsTo(NqfLevel::class, 'nqf_level_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'grade_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'grade_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'grade_id');
    }

    public function userSubjectPreferences(): HasMany
    {
        return $this->hasMany(UserSubjectPreference::class, 'grade_id');
    }

    public function userSubjectResults(): HasMany
    {
        return $this->hasMany(UserSubjectResult::class, 'grade_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'grade_id');
    }
}
