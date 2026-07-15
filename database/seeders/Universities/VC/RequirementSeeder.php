<?php

namespace Database\Seeders\Universities\VC;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'VC';
    }

    protected function universityName(): string
    {
        return "The IIE's Varsity College";
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/VC/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'nsc_pass_type';
    }

    protected function website(): ?string
    {
        return 'https://www.emeris.ac.za/';
    }
}
