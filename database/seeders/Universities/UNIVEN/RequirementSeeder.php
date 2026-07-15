<?php

namespace Database\Seeders\Universities\UNIVEN;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UNIVEN';
    }

    protected function universityName(): string
    {
        return 'University of Venda';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UNIVEN/Requirements/*.json';
    }
}
