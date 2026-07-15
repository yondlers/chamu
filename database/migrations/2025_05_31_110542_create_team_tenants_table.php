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
        Schema::create('team_tenants', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);

            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('cascade'); // FK to units table, to find  desired property

            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade'); // FK to teams table

            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade'); // FK to tenants table

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_tenants');
    }
};
