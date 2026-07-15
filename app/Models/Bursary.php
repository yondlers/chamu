<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
