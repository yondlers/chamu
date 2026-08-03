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

    protected function website(): ?string
    {
        return 'https://www.ukzn.ac.za';
    }

    protected function defaultSourceUrl(): ?string
    {
        return 'https://studyatukzn.ukzn.ac.za/wp-content/uploads/2021/07/Degree-Requirements-Guide-2020-Rev_2.pdf';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UKZN/Requirements/*.json';
    }
}
