<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualificationType extends Model
{
    use HasFactory;

    protected $table = 'qualification_types';

    protected $fillable = [
        'nqf_level_id',
        'name',
        'abbreviation',
        'sort_order',
    ];

    public function nqfLevel(): BelongsTo
    {
        return $this->belongsTo(NqfLevel::class, 'nqf_level_id');
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class, 'qualification_type_id');
    }
}
