<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'user_type_id']);
        });

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'user_type_id')) {
            $now = now();

            DB::table('users')
                ->select('id', 'user_type_id')
                ->whereNotNull('user_type_id')
                ->orderBy('id')
                ->chunkById(200, function ($users) use ($now): void {
                    $rows = $users->map(fn ($user) => [
                        'user_id' => $user->id,
                        'user_type_id' => $user->user_type_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    if ($rows !== []) {
                        DB::table('user_roles')->insertOrIgnore($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
