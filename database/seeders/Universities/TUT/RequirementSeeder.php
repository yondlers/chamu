<?php

namespace Database\Seeders\Universities\TUT;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'TUT';
    }

    protected function universityName(): string
    {
        return 'Tshwane University of Technology';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/TUT/Requirements/*.json';
    }
}
