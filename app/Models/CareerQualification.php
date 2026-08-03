<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerQualification extends Model
{
    use HasFactory;

    protected $table = 'career_qualification';

    protected $fillable = [
        'career_id',
        'qualification_id',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class, 'career_id');
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class, 'qualification_id');
    }
}
