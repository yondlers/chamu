<?php

namespace Database\Seeders\Universities\DUT;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'DUT';
    }

    protected function universityName(): string
    {
        return 'Durban University of Technology';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/DUT/Requirements/*.json';
    }

    protected function defaultSourceUrl(): ?string
    {
        return 'https://www.dut.ac.za/wp-content/uploads/2026/06/Study-Opportunities-2027.pdf';
    }
}
