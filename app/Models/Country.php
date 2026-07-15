<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'name',
    ];

    public function curriculums(): HasMany
    {
        return $this->hasMany(Curriculum::class, 'country_id');
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class, 'country_id');
    }

    public function universities(): HasMany
    {
        return $this->hasMany(University::class, 'country_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'country_id');
    }
}
