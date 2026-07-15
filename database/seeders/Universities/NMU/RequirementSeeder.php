<?php

namespace Database\Seeders\Universities\NMU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'NMU';
    }

    protected function universityName(): string
    {
        return 'Nelson Mandela University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/NMU/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'nmu_applicant_score';
    }
}
