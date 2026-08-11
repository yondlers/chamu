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
        Schema::table('universities', function (Blueprint $table) {
            $table->text('postal_address')->nullable()->after('website');
            $table->text('physical_address')->nullable()->after('postal_address');
            $table->string('contact_email')->nullable()->after('physical_address');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('contact_fax')->nullable()->after('contact_phone');
            $table->decimal('latitude', 10, 7)->nullable()->after('contact_fax');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('contact_source_url')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropColumn([
                'postal_address',
                'physical_address',
                'contact_email',
                'contact_phone',
                'contact_fax',
                'latitude',
                'longitude',
                'contact_source_url',
            ]);
        });
    }
};
