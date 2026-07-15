<?php

namespace Database\Seeders\Universities\UCT;

use Database\Seeders\Universities\UniversityRequirementSeeder;

class RequirementSeeder extends UniversityRequirementSeeder
{
    protected function abbreviation(): string
    {
        return 'UCT';
    }

    protected function universityName(): string
    {
        return 'University of Cape Town';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/UCT/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'uct_fps_600_excluding_lo';
    }

    protected function facultyAdmissionRuleCode(array $facultyData): ?string
    {
        return match ($facultyData['faculty'] ?? null) {
            'Health Sciences' => 'uct_fps_900_health_sciences',
            'Science' => 'uct_fps_800_science',
            default => null,
        };
    }
}
