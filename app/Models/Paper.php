<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paper extends Model
{
    use HasFactory;

    protected $table = 'papers';

    protected $fillable = [
        'number',
    ];

    public function pastPaperQuestions(): HasMany
    {
        return $this->hasMany(PastPaperQuestion::class, 'paper_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'paper_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'paper_id');
    }
}
