<?php

namespace Database\Seeders\Universities\EDUVOS;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'EDUVOS';
    }

    protected function universityName(): string
    {
        return 'Eduvos';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/EDUVOS/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'eduvos_points';
    }

    protected function website(): ?string
    {
        return 'https://www.eduvos.com/';
    }
}
