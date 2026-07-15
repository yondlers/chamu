<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    use HasFactory;

    protected $table = 'curriculums';

    protected $fillable = [
        'country_id',
        'name',
        'abbreviation',
        'is_live',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function charadeCategories(): HasMany
    {
        return $this->hasMany(CharadeCategory::class, 'curriculum_id');
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'curriculum_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'curriculum_id');
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'curriculum_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'curriculum_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'curriculum_id');
    }

    public function userSubjectPreferences(): HasMany
    {
        return $this->hasMany(UserSubjectPreference::class, 'curriculum_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'curriculum_id');
    }
}
