<?php

namespace Database\Seeders\Universities\UJ;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UJ';
    }

    protected function universityName(): string
    {
        return 'University of Johannesburg';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UJ/Requirements/*.json';
    }
}
