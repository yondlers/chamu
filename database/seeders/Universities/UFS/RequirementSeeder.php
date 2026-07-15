<?php

namespace Database\Seeders\Universities\UFS;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UFS';
    }

    protected function universityName(): string
    {
        return 'University of the Free State';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UFS/Requirements/*.json';
    }
}
