<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bursaries', function (Blueprint $table) {
            $table->text('application_postal_address')->nullable()->after('application_email');
        });

        Schema::table('bursary_applications', function (Blueprint $table) {
            $table->string('delivery_type')->default('email')->after('status');
            $table->text('provider_postal_address')->nullable()->after('provider_email');
            $table->text('applicant_postal_address')->nullable()->after('applicant_phone');
        });
    }

    public function down(): void
    {
        Schema::table('bursary_applications', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'provider_postal_address',
                'applicant_postal_address',
            ]);
        });

        Schema::table('bursaries', function (Blueprint $table) {
            $table->dropColumn('application_postal_address');
        });
    }
};
