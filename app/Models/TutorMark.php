<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorMark extends Model
{
    use HasFactory;

    protected $table = 'tutor_marks';

    protected $fillable = [
        'tutor_application_id',
        'subject_id',
        'subject_other',
        'mark',
        'year',
        'level',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'mark' => 'integer',
            'year' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function tutorApplication(): BelongsTo
    {
        return $this->belongsTo(TutorApplication::class, 'tutor_application_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function displaySubjectName(): string
    {
        if (filled($this->subject_other)) {
            return (string) $this->subject_other;
        }

        return (string) ($this->subject?->name ?? 'Subject');
    }
}
