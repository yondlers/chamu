<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Bursary extends Model
{
    use HasFactory;

    protected $table = 'bursaries';

    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'category',
        'summary',
        'fields_covered',
        'coverage_value',
        'service_contract',
        'renewal',
        'eligibility_requirements',
        'application_method',
        'application_delivery_type',
        'application_email',
        'application_postal_address',
        'chamu_apply_enabled',
        'supporting_documents',
        'closing_date',
        'closing_date_label',
        'contact_name',
        'contact_email',
        'contact_phone',
        'source_url',
        'apply_url',
        'is_active',
    ];

    protected $casts = [
        'eligibility_requirements' => 'array',
        'supporting_documents' => 'array',
        'closing_date' => 'date',
        'chamu_apply_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function subjectRequirements(): HasMany
    {
        return $this->hasMany(BursarySubjectRequirement::class, 'bursary_id');
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(BursaryDocumentRequirement::class, 'bursary_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(BursaryApplication::class, 'bursary_id');
    }

    public function applicationProviderEmail(): ?string
    {
        if (filled($this->application_email)) {
            return $this->application_email;
        }

        if (filled($this->contact_email)) {
            return $this->contact_email;
        }

        if (Str::startsWith((string) $this->apply_url, 'mailto:')) {
            return Str::of($this->apply_url)
                ->after('mailto:')
                ->before('?')
                ->trim()
                ->toString();
        }

        return null;
    }

    public function applicationProviderPostalAddress(): ?string
    {
        if (filled($this->application_postal_address)) {
            return $this->application_postal_address;
        }

        if (($this->application_delivery_type ?? null) === 'postal') {
            return 'Chamu will verify the provider postal or hand-delivery address from the bursary instructions before dispatch.';
        }

        return null;
    }

    public function isEmailSubmission(): bool
    {
        return ($this->application_delivery_type ?? null) === 'email'
            || Str::startsWith((string) $this->apply_url, 'mailto:')
            || Str::contains(Str::lower((string) $this->application_method), [
                'apply by email',
                'email to',
                'via email',
                'submitted by email',
            ]);
    }

    public function isPostalSubmission(): bool
    {
        return ($this->application_delivery_type ?? null) === 'postal'
            || Str::contains(Str::lower((string) $this->application_method), [
                'apply by post',
                'submit by post',
                'submitted by post',
                'via post',
                'postal',
                'po box',
                'p.o. box',
                'private bag',
                'hand delivery',
                'deliver to',
            ]);
    }

    public function isChamuSubmission(): bool
    {
        return (bool) ($this->chamu_apply_enabled ?? false)
            && ($this->isEmailSubmission() || $this->isPostalSubmission());
    }
}
