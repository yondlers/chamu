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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('user_type_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->after('user_type_id')->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->after('school_id')->constrained('users')->nullOnDelete();
            $table->foreignId('curriculum_id')->after('parent_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->after('curriculum_id')->constrained()->nullOnDelete();
            $table->foreignId('country_id')->after('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->string('first_name')->after('province_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->unique()->after('last_name');
            $table->string('avatar')->nullable()->after('password');
            $table->string('profile_picture')->nullable()->after('avatar');
            $table->unsignedInteger('points')->default(0)->after('profile_picture');
            $table->unsignedInteger('streak')->default(0)->after('points');
            $table->timestamp('last_login_at')->nullable()->after('streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['user_type_id']);
            $table->dropForeign(['school_id']);
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['curriculum_id']);
            $table->dropForeign(['grade_id']);
            $table->dropForeign(['country_id']);
            $table->dropForeign(['province_id']);
            $table->dropUnique(['username']);
            $table->dropColumn([
                'user_type_id',
                'school_id',
                'parent_id',
                'curriculum_id',
                'grade_id',
                'country_id',
                'province_id',
                'first_name',
                'last_name',
                'username',
                'avatar',
                'profile_picture',
                'points',
                'streak',
                'last_login_at',
            ]);
        });
    }
};
