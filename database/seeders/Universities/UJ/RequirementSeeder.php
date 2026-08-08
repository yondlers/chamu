<?php

namespace Database\Seeders\Universities\UJ;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UJ';
    }

    protected function universityName(): string
    {
        return 'University of Johannesburg';
    }

    protected function website(): ?string
    {
        return 'https://www.uj.ac.za';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UJ/Requirements/*.json';
    }

    protected function defaultSourceUrl(): ?string
    {
        return 'https://www.uj.ac.za/wp-content/uploads/2025/03/2026-undergraduate-prospectus.pdf';
    }
}
