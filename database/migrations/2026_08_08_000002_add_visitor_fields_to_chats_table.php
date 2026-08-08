<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('guest_token');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('device_type', 40)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'device_type']);
        });
    }
};
