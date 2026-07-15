<?php

namespace Database\Seeders\Universities\UWC;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UWC';
    }

    protected function universityName(): string
    {
        return 'University of the Western Cape';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UWC/Requirements/*.json';
    }
}
