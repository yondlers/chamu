<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Career extends Model
{
    use HasFactory;

    protected $table = 'careers';

    protected $fillable = [
        'name',
        'salary_expectation',
        'description',
        'source_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function qualifications(): BelongsToMany
    {
        return $this->belongsToMany(Qualification::class, 'career_qualification', 'career_id', 'qualification_id')
            ->withPivot(['sort_order', 'notes'])
            ->withTimestamps();
    }

    public function careerQualifications(): HasMany
    {
        return $this->hasMany(CareerQualification::class, 'career_id');
    }
}
