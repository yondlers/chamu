<?php

namespace Database\Seeders\Universities\UKZN;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UKZN';
    }

    protected function universityName(): string
    {
        return 'University of KwaZulu-Natal';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UKZN/Requirements/*.json';
    }
}
