<?php

namespace Database\Seeders\Universities\UMP;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UMP';
    }

    protected function universityName(): string
    {
        return 'University of Mpumalanga';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UMP/Requirements/*.json';
    }
}
