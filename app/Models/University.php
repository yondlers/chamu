<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class University extends Model
{
    use HasFactory;

    protected $table = 'universities';

    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'abbreviation',
        'logo',
        'website',
        'postal_address',
        'physical_address',
        'contact_email',
        'contact_phone',
        'contact_fax',
        'latitude',
        'longitude',
        'contact_source_url',
        'default_closing_month',
        'default_closing_day',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function hasContactDetails(): bool
    {
        return filled($this->physical_address)
            || filled($this->postal_address)
            || filled($this->contact_email)
            || filled($this->contact_phone)
            || ($this->latitude !== null && $this->longitude !== null);
    }

    public function hasMapCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    protected static function booted(): void
    {
        static::creating(function (University $university): void {
            if ($university->slug) {
                return;
            }

            $base = Str::slug((string) $university->name) ?: 'university';
            $slug = $base;
            $suffix = 2;

            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }

            $university->slug = $slug;
        });
    }

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

    public function universityAdmissionRules(): HasMany
    {
        return $this->hasMany(UniversityAdmissionRule::class, 'university_id');
    }

    public function tutorApplications(): HasMany
    {
        return $this->hasMany(TutorApplication::class, 'university_id');
    }
}
