<?php

namespace Database\Seeders\Universities\SMU;

use Database\Seeders\Universities\UniversityRequirementSeeder;
use Illuminate\Support\Facades\DB;

class RequirementSeeder extends UniversityRequirementSeeder
{
    private const MEDICINE_PROGRAMMES_MOVED_FROM_SCIENCE = [
        'Bachelor of Diagnostic Radiography',
        'Diploma in Emergency Medical Care',
        'Higher Certificate in Emergency Medical Care',
    ];

    public function run(): void
    {
        $this->relocateMedicineProgrammesFromScience();

        parent::run();
    }

    protected function abbreviation(): string
    {
        return 'SMU';
    }

    protected function universityName(): string
    {
        return 'Sefako Makgatho Health Sciences University';
    }

    protected function requirementsPath(): string
    {
        return 'seeders/Universities/SMU/Requirements/*.json';
    }

    protected function admissionRuleCode(): string
    {
        return 'nsc_aps_including_lo';
    }

    protected function website(): ?string
    {
        return 'https://www.smu.ac.za/';
    }

    private function relocateMedicineProgrammesFromScience(): void
    {
        $universityId = DB::table('universities')
            ->where('abbreviation', $this->abbreviation())
            ->value('id');

        if ($universityId === null) {
            return;
        }

        $medicineFacultyId = DB::table('faculties')
            ->where('university_id', $universityId)
            ->where('name', 'School of Medicine')
            ->value('id');

        $scienceFacultyId = DB::table('faculties')
            ->where('university_id', $universityId)
            ->where('name', 'School of Science and Technology')
            ->value('id');

        if ($medicineFacultyId === null || $scienceFacultyId === null) {
            return;
        }

        foreach (self::MEDICINE_PROGRAMMES_MOVED_FROM_SCIENCE as $programmeName) {
            $targetExists = DB::table('qualifications')
                ->where('university_id', $universityId)
                ->where('faculty_id', $medicineFacultyId)
                ->where('name', $programmeName)
                ->exists();

            $sourceQuery = DB::table('qualifications')
                ->where('university_id', $universityId)
                ->where('faculty_id', $scienceFacultyId)
                ->where('name', $programmeName);

            if ($targetExists) {
                $sourceQuery->delete();

                continue;
            }

            $sourceQuery->update([
                'faculty_id' => $medicineFacultyId,
                'updated_at' => now(),
            ]);
        }
    }
}
