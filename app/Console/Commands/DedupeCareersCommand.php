<?php

namespace App\Console\Commands;

use App\Models\Career;
use App\Models\CareerQualification;
use App\Support\CareerUpsert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time careers cleanup for production.
 *
 * Why this exists (2026-08-10):
 * Career rows were seeded from university programme JSON with inconsistent casing,
 * plurals, and broken list fragments (e.g. "Manager and", "System analyst" vs
 * "Systems Analyst"). Before importing salary expectations we need a single
 * canonical career per role. This command runs every existing careers row through
 * App\Support\CareerUpsert (normalize + match-key dedupe), merges qualification
 * links onto the keeper, and deletes duplicate rows.
 *
 * Intended usage: run once on live after deploy. Safe to re-run (idempotent), but
 * not needed as an ongoing job once careers are clean and all writes go through
 * CareerUpsert.
 */
class DedupeCareersCommand extends Command
{
    protected $signature = 'careers:dedupe
                            {--dry-run : Show merges/deletes without writing}
                            {--force : Skip confirmation prompt}';

    protected $description = 'One-time (2026-08-10): normalize careers and merge duplicates via CareerUpsert before salary import';

    public function handle(CareerUpsert $careerUpsert): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->warn('careers:dedupe — one-time cleanup created 2026-08-10 to collapse seeded career duplicates before salary import.');

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Merge duplicate careers in the database?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $careers = Career::query()
            ->withCount('careerQualifications')
            ->orderBy('id')
            ->get();

        $groups = [];
        $unusable = [];

        foreach ($careers as $career) {
            $normalized = $careerUpsert->normalizeName($career->name);

            if ($normalized === null) {
                $unusable[] = $career;

                continue;
            }

            $key = $careerUpsert->matchKey($normalized);
            $groups[$key][] = $career;
        }

        $mergeGroups = collect($groups)->filter(fn (array $group) => count($group) > 1);
        $singletonCount = collect($groups)->filter(fn (array $group) => count($group) === 1)->count();

        $this->info(sprintf(
            'Scanned %d careers → %d unique keys, %d duplicate groups, %d unusable names%s',
            $careers->count(),
            count($groups),
            $mergeGroups->count(),
            count($unusable),
            $dryRun ? ' (dry-run)' : '',
        ));

        $mergedAway = 0;
        $renamed = 0;
        $deletedUnusable = 0;

        DB::transaction(function () use (
            $careerUpsert,
            $groups,
            $unusable,
            $dryRun,
            &$mergedAway,
            &$renamed,
            &$deletedUnusable,
        ): void {
            foreach ($groups as $matchKey => $group) {
                $keeper = $this->pickKeeper($group);
                $normalizedName = $careerUpsert->normalizeName($keeper->name);

                if ($normalizedName === null) {
                    continue;
                }

                foreach ($group as $candidate) {
                    if ((int) $candidate->id === (int) $keeper->id) {
                        continue;
                    }

                    $this->line(sprintf(
                        '  merge #%d [%s] → #%d [%s] (key: %s)',
                        $candidate->id,
                        $candidate->name,
                        $keeper->id,
                        $keeper->name,
                        $matchKey,
                    ));

                    if (! $dryRun) {
                        $careerUpsert->mergeInto($keeper, $candidate);
                    }

                    $mergedAway++;
                }

                if ($dryRun) {
                    if ($keeper->name !== $normalizedName) {
                        $this->line(sprintf(
                            '  rename #%d [%s] → [%s]',
                            $keeper->id,
                            $keeper->name,
                            $normalizedName,
                        ));
                        $renamed++;
                    }

                    continue;
                }

                $before = $keeper->name;
                $result = $careerUpsert->update($keeper->fresh() ?? $keeper, $normalizedName, [
                    'salary_expectation' => $keeper->salary_expectation,
                    'description' => $keeper->description,
                    'source_url' => $keeper->source_url,
                    'is_active' => true,
                ]);

                if ($result !== null && $before !== $result['career']->name) {
                    $this->line(sprintf(
                        '  rename #%d [%s] → [%s]',
                        $result['id'],
                        $before,
                        $result['career']->name,
                    ));
                    $renamed++;
                }
            }

            foreach ($unusable as $career) {
                $linkCount = (int) ($career->career_qualifications_count ?? CareerQualification::query()->where('career_id', $career->id)->count());

                if ($linkCount > 0) {
                    $this->warn(sprintf(
                        '  skip unusable #%d [%s] — still linked to %d qualification(s)',
                        $career->id,
                        $career->name,
                        $linkCount,
                    ));

                    continue;
                }

                $this->line(sprintf('  delete unusable #%d [%s]', $career->id, $career->name));

                if (! $dryRun) {
                    $career->delete();
                }

                $deletedUnusable++;
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Done%s: %d merged away, %d renamed, %d unusable deleted, %d singleton keys left untouched aside from rename pass.',
            $dryRun ? ' (dry-run)' : '',
            $mergedAway,
            $renamed,
            $deletedUnusable,
            $singletonCount,
        ));

        if ($dryRun) {
            $this->comment('Re-run without --dry-run to apply. Prefer: php artisan careers:dedupe --force');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, Career>  $group
     */
    private function pickKeeper(array $group): Career
    {
        return collect($group)
            ->sort(function (Career $a, Career $b): int {
                $linkA = (int) ($a->career_qualifications_count ?? 0);
                $linkB = (int) ($b->career_qualifications_count ?? 0);
                if ($linkA !== $linkB) {
                    return $linkB <=> $linkA;
                }

                $salaryA = filled($a->salary_expectation) ? 1 : 0;
                $salaryB = filled($b->salary_expectation) ? 1 : 0;
                if ($salaryA !== $salaryB) {
                    return $salaryB <=> $salaryA;
                }

                return $a->id <=> $b->id;
            })
            ->first();
    }
}
