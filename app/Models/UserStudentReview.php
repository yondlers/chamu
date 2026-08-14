<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStudentReview extends Model
{
    use HasFactory;

    protected $table = 'user_student_reviews';

    protected $fillable = [
        'user_id',
        'curriculum_id',
        'grade_id',
        'term_id',
        'snapshot_hash',
        'status',
        'review_text',
        'subject_count',
        'marked_subject_count',
        'aps_total',
        'average_mark',
        'qualified_count',
        'provider',
        'model',
        'payload',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'average_mark' => 'float',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }
}
