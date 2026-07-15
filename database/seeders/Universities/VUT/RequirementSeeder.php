<?php

namespace Database\Seeders\Universities\VUT;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'VUT';
    }

    protected function universityName(): string
    {
        return 'Vaal University of Technology';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/VUT/Requirements/*.json';
    }
}
