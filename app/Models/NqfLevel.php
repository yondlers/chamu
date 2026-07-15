<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NqfLevel extends Model
{
    use HasFactory;

    protected $table = 'nqf_levels';

    protected $fillable = [
        'level',
        'name',
        'category',
        'description',
        'sort_order',
    ];

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'nqf_level_id');
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class, 'nqf_level_id');
    }

    public function qualificationTypes(): HasMany
    {
        return $this->hasMany(QualificationType::class, 'nqf_level_id');
    }
}
