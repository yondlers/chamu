<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $table = 'terms';

    protected $fillable = [
        'curriculum_id',
        'grade_id',
        'name',
        'from_date',
        'to_date',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'term_id');
    }

    public function userSubjectResults(): HasMany
    {
        return $this->hasMany(UserSubjectResult::class, 'term_id');
    }
}
