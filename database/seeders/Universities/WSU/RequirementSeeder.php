<?php

namespace Database\Seeders\Universities\WSU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'WSU';
    }

    protected function universityName(): string
    {
        return 'Walter Sisulu University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/WSU/Requirements/*.json';
    }
}
