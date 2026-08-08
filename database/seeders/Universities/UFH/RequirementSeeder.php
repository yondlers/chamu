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

    protected function website(): ?string
    {
        return 'https://www.ufh.ac.za';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UFH/Requirements/*.json';
    }

    protected function defaultSourceUrl(): ?string
    {
        return 'https://www.ufh.ac.za/wp-content/uploads/2026/06/Study-Guide-2027.pdf';
    }
}
