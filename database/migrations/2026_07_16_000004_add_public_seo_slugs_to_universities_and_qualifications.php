<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->unique('slug', 'universities_slug_unique');
        });

        Schema::table('qualifications', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->unique(['university_id', 'slug'], 'qualifications_university_slug_unique');
        });

        $this->backfillUniversitySlugs();
        $this->backfillQualificationSlugs();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropUnique('qualifications_university_slug_unique');
            $table->dropColumn('slug');
        });

        Schema::table('universities', function (Blueprint $table) {
            $table->dropUnique('universities_slug_unique');
            $table->dropColumn('slug');
        });
    }

    private function backfillUniversitySlugs(): void
    {
        $used = [];

        DB::table('universities')
            ->select('id', 'name', 'slug')
            ->orderBy('id')
            ->get()
            ->each(function (object $university) use (&$used): void {
                if ($university->slug !== null && $university->slug !== '') {
                    $used[$university->slug] = true;

                    return;
                }

                $base = Str::slug((string) $university->name) ?: 'university-'.$university->id;
                $slug = $base;

                if (isset($used[$slug])) {
                    $slug = $base.'-'.$university->id;
                }

                $used[$slug] = true;

                DB::table('universities')
                    ->where('id', $university->id)
                    ->update(['slug' => $slug]);
            });
    }

    private function backfillQualificationSlugs(): void
    {
        $usedByUniversity = [];

        DB::table('qualifications')
            ->leftJoin('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->select(
                'qualifications.id',
                'qualifications.university_id',
                'qualifications.name',
                'qualifications.slug',
                'qualification_types.abbreviation as qualification_type_abbreviation',
                'qualification_types.name as qualification_type_name',
            )
            ->orderBy('qualifications.university_id')
            ->orderBy('qualifications.id')
            ->get()
            ->each(function (object $qualification) use (&$usedByUniversity): void {
                $universityId = (int) $qualification->university_id;
                $usedByUniversity[$universityId] ??= [];

                if ($qualification->slug !== null && $qualification->slug !== '') {
                    $usedByUniversity[$universityId][$qualification->slug] = true;

                    return;
                }

                $base = Str::slug((string) $qualification->name) ?: 'qualification-'.$qualification->id;
                $slug = $base;

                if (isset($usedByUniversity[$universityId][$slug])) {
                    $typeSlug = Str::slug((string) ($qualification->qualification_type_abbreviation ?: $qualification->qualification_type_name));
                    $slug = $typeSlug ? $base.'-'.$typeSlug : $base.'-'.$qualification->id;
                }

                if (isset($usedByUniversity[$universityId][$slug])) {
                    $slug = $base.'-'.$qualification->id;
                }

                $usedByUniversity[$universityId][$slug] = true;

                DB::table('qualifications')
                    ->where('id', $qualification->id)
                    ->update(['slug' => $slug]);
            });
    }
};
