<?php

namespace Database\Seeders\Universities\SPU;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'SPU';
    }

    protected function universityName(): string
    {
        return 'Sol Plaatje University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/SPU/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'spu_admission_points';
    }

    protected function website(): ?string
    {
        return 'https://www.spu.ac.za/';
    }
}
