<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class University extends Model
{
    use HasFactory;

    protected $table = 'universities';

    protected $fillable = [
        'country_id',
        'name',
        'abbreviation',
        'logo',
        'website',
        'default_closing_month',
        'default_closing_day',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class, 'university_id');
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class, 'university_id');
    }
}
