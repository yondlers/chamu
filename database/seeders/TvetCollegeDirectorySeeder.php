<?php

namespace Database\Seeders;

use App\Support\TvetColleges;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Upsert every public TVET college from the DHET directory.
 * Colleges without programme catalogues are still created so they can be seeded later.
 *
 * @see https://www.dhet.gov.za/RegionalOffices/educational-institutions/technical-vocational-education-and-training-colleges-tvet-colleges.html
 */
class TvetCollegeDirectorySeeder extends Seeder
{
    /**
     * Existing catalogue names we keep so slug/branding stays stable.
     *
     * @var array<string, string>
     */
    private const PRESERVE_NAMES = [
        'BOLAND' => 'Boland College',
        'WESTCOL' => 'Westcol TVET College',
    ];

    /**
     * Prefer already-curated websites from requirement seeders.
     *
     * @var array<string, true>
     */
    private const PRESERVE_WEBSITES = [
        'BCC' => true,
        'BOLAND' => true,
        'CJC' => true,
        'EEC' => true,
        'EHLANZENI' => true,
        'EWC' => true,
        'SCC' => true,
        'TNC' => true,
        'TSC' => true,
        'WESTCOL' => true,
    ];

    public function run(): void
    {
        $countryId = DB::table('countries')->where('name', 'South Africa')->value('id');

        if ($countryId === null) {
            DB::table('countries')->insert([
                'name' => 'South Africa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $countryId = (int) DB::table('countries')->where('name', 'South Africa')->value('id');
        }

        $countryId = (int) $countryId;
        $now = now();
        $hasContactColumns = Schema::hasColumn('universities', 'contact_phone');
        $hasSlug = Schema::hasColumn('universities', 'slug');

        foreach (TvetColleges::directory() as $abbreviation => $college) {
            $existing = DB::table('universities')
                ->where('abbreviation', $abbreviation)
                ->first();

            $name = self::PRESERVE_NAMES[$abbreviation]
                ?? $existing?->name
                ?? $college['name'];

            $website = (isset(self::PRESERVE_WEBSITES[$abbreviation]) && filled($existing?->website))
                ? $existing->website
                : ($college['website'] ?? $existing?->website);

            $values = [
                'country_id' => $countryId,
                'name' => $name,
                'website' => $website,
                'updated_at' => $now,
            ];

            if ($existing === null) {
                $values['created_at'] = $now;
                $values['logo'] = UniversityLogoSeeder::logoFor($abbreviation);
            }

            if ($hasSlug) {
                $values['slug'] = $existing?->slug ?: $this->uniqueSlug($college['name'], $existing?->id ?? null);
            }

            if ($hasContactColumns) {
                $values['contact_phone'] = $college['phone'];
                $values['latitude'] = $college['latitude'];
                $values['longitude'] = $college['longitude'];
                $values['contact_source_url'] = TvetColleges::SOURCE_URL;
                $values['physical_address'] = $college['physical_address']
                    ?? $existing?->physical_address
                    ?? ($college['province'].', South Africa');
            }

            DB::table('universities')->updateOrInsert(
                ['abbreviation' => $abbreviation],
                $values,
            );
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'tvet-college';
        $slug = $base;
        $suffix = 2;

        while (
            DB::table('universities')
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
