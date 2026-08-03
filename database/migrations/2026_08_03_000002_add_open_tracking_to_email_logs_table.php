<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->unsignedInteger('open_count')->default(0)->after('last_error');
            $table->timestamp('first_opened_at')->nullable()->index()->after('open_count');
            $table->timestamp('last_opened_at')->nullable()->index()->after('first_opened_at');
            $table->string('last_open_ip_address', 45)->nullable()->after('last_opened_at');
            $table->text('last_open_user_agent')->nullable()->after('last_open_ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn([
                'open_count',
                'first_opened_at',
                'last_opened_at',
                'last_open_ip_address',
                'last_open_user_agent',
            ]);
        });
    }
};
