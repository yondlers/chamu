<?php

namespace Database\Seeders\Universities\UWC;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UWC';
    }

    protected function universityName(): string
    {
        return 'University of the Western Cape';
    }

    protected function website(): ?string
    {
        return 'https://www.uwc.ac.za';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UWC/Requirements/*.json';
    }

    protected function defaultSourceUrl(): ?string
    {
        return 'https://uwc-za.b-cdn.net/files/files/Admission-Requirements-2025.pdf';
    }
}
