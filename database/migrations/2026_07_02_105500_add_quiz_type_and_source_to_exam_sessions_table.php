<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_sessions', 'quiz_type')) {
                $table->string('quiz_type')->nullable()->after('paper_type');
            }

            if (! Schema::hasColumn('exam_sessions', 'source')) {
                $table->string('source')->nullable()->after('quiz_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_sessions', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('exam_sessions', 'quiz_type')) {
                $table->dropColumn('quiz_type');
            }
        });
    }
};
