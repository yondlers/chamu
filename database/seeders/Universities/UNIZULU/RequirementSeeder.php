<?php

namespace Database\Seeders\Universities\UNIZULU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UNIZULU';
    }

    protected function universityName(): string
    {
        return 'University of Zululand';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UNIZULU/Requirements/*.json';
    }
}
