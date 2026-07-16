<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StellenboschRequirementSeedDataTest extends TestCase
{
    public function test_baccllb_seed_entries_include_aggregate_average_and_subject_choices(): void
    {
        $entries = collect([
            dirname(__DIR__, 2).'/database/seeders/Universities/SU/Requirements/economic_and_management_sciences.json',
            dirname(__DIR__, 2).'/database/seeders/Universities/SU/Requirements/law.json',
        ])->flatMap(function (string $path) {
            $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            return collect($data['qualifications'])
                ->where('name', 'BAccLLB')
                ->values();
        });

        $this->assertCount(2, $entries);

        foreach ($entries as $entry) {
            $this->assertSame(80.0, (float) $entry['aggregate_average_required']);

            $choiceRequirement = collect($entry['subject_requirements'])
                ->firstWhere('type', 'subject_group_count_choices');

            $this->assertNotNull($choiceRequirement);

            $choiceLabels = collect($choiceRequirement['choices'])->pluck('label')->all();

            $this->assertContains('Mathematics 70%', $choiceLabels);
            $this->assertContains('Mathematics 60% and Accounting 70%', $choiceLabels);
        }
    }
}
