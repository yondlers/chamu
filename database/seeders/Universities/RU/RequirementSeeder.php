<?php

namespace Database\Seeders\Universities\RU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'RU';
    }

    protected function universityName(): string
    {
        return 'Rhodes University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/RU/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'percentage_aps_6_excluding_lo';
    }
}
