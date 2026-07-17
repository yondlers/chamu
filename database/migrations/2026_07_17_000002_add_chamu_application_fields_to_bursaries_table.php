<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bursaries', function (Blueprint $table) {
            $table->string('application_delivery_type')->default('external_link')->after('application_method');
            $table->string('application_email')->nullable()->after('application_delivery_type');
            $table->boolean('chamu_apply_enabled')->default(false)->after('application_email');
        });
    }

    public function down(): void
    {
        Schema::table('bursaries', function (Blueprint $table) {
            $table->dropColumn([
                'application_delivery_type',
                'application_email',
                'chamu_apply_enabled',
            ]);
        });
    }
};
