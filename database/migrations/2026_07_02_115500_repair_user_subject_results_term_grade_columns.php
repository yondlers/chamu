<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_subject_results', function (Blueprint $table) {
            if (! Schema::hasColumn('user_subject_results', 'grade_id')) {
                $table->foreignId('grade_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('user_subject_results', 'term_id')) {
                $table->foreignId('term_id')->nullable()->after('grade_id')->constrained()->nullOnDelete();
            }
        });

        $this->dropIndexIfExists('user_subject_results_user_id_subject_id_unique');
        $this->createIndexIfMissing(
            'user_subject_results_user_grade_term_subject_unique',
            'CREATE UNIQUE INDEX user_subject_results_user_grade_term_subject_unique ON user_subject_results (user_id, grade_id, term_id, subject_id)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('user_subject_results_user_grade_term_subject_unique');
        $this->createIndexIfMissing(
            'user_subject_results_user_id_subject_id_unique',
            'CREATE UNIQUE INDEX user_subject_results_user_id_subject_id_unique ON user_subject_results (user_id, subject_id)'
        );
    }

    private function dropIndexIfExists(string $indexName): void
    {
        if ($this->indexExists($indexName)) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("DROP INDEX {$indexName} ON user_subject_results");

                return;
            }

            DB::statement("DROP INDEX {$indexName}");
        }
    }

    private function createIndexIfMissing(string $indexName, string $statement): void
    {
        if (! $this->indexExists($indexName)) {
            DB::statement($statement);
        }
    }

    private function indexExists(string $indexName): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return collect(DB::select("SHOW INDEX FROM user_subject_results WHERE Key_name = ?", [$indexName]))
                ->isNotEmpty();
        }

        return collect(DB::select("PRAGMA index_list('user_subject_results')"))
            ->contains(fn ($index) => $index->name === $indexName);
    }
};
