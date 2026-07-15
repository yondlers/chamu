<?php

namespace Database\Seeders\Universities\CPUT;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'CPUT';
    }

    protected function universityName(): string
    {
        return 'Cape Peninsula University of Technology';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/CPUT/Requirements/*.json';
    }
}
