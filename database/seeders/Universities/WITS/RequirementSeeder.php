<?php

namespace Database\Seeders\Universities\WITS;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'WITS';
    }

    protected function universityName(): string
    {
        return 'University of the Witwatersrand';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/WITS/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'wits_aps';
    }

    protected function facultyAdmissionRuleCode(array $facultyData): ?string
    {
        return ($facultyData['admission_score_type'] ?? null) === 'Composite Index and subject levels'
            ? 'subject_levels_only'
            : null;
    }
}
