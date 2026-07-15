<?php

namespace Database\Seeders\Universities\SU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'SU';
    }

    protected function universityName(): string
    {
        return 'Stellenbosch University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/SU/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'nsc_aggregate_excluding_lo';
    }
}
