<?php

namespace Database\Seeders\Universities\UL;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UL';
    }

    protected function universityName(): string
    {
        return 'University of Limpopo';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UL/Requirements/*.json';
    }
}
