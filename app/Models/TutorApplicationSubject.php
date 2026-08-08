<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorApplicationSubject extends Model
{
    use HasFactory;

    protected $table = 'tutor_application_subjects';

    protected $fillable = [
        'tutor_application_id',
        'subject_name',
        'hourly_rate',
        'level',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TutorApplication::class, 'tutor_application_id');
    }
}
