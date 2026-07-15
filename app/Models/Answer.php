<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Answer extends Model
{
    use HasFactory;

    protected $table = 'answers';

    protected $fillable = [
        'correct_answer',
        'accepted_answers',
        'explanation',
        'answer_type',
        'is_case_sensitive',
        'requires_exact_match',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'answer_id');
    }

    public function subQuestions(): HasMany
    {
        return $this->hasMany(SubQuestion::class, 'answer_id');
    }
}
