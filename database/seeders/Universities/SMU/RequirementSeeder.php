<?php

namespace Database\Seeders\Universities\SMU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'SMU';
    }

    protected function universityName(): string
    {
        return 'Sefako Makgatho Health Sciences University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/SMU/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'nsc_aps_including_lo';
    }

    protected function website(): ?string
    {
        return 'https://www.smu.ac.za/';
    }
}
