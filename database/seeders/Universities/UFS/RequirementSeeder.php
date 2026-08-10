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

    protected function website(): ?string
    {
        return 'https://www.ufs.ac.za';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UFS/Requirements/*.json';
    }

    protected function defaultSourceUrl(): ?string
    {
        return 'https://www.ufs.ac.za/docs/librariesprovider44/prospectus/ug-prospectus-2027.pdf?sfvrsn=49a7fa20_3';
    }
}
