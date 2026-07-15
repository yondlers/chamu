<?php

namespace Database\Seeders\Universities\UFH;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UFH';
    }

    protected function universityName(): string
    {
        return 'University of Fort Hare';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UFH/Requirements/*.json';
    }
}
