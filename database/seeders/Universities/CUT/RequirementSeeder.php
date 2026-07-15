<?php

namespace Database\Seeders\Universities\CUT;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'CUT';
    }

    protected function universityName(): string
    {
        return 'Central University of Technology';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/CUT/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'nsc_aps_including_lo';
    }
}
