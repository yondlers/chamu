<?php

namespace Database\Seeders\Universities\NWU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'NWU';
    }

    protected function universityName(): string
    {
        return 'North-West University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/NWU/Requirements/*.json';
    }
}
