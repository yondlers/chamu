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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);

            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('listing_id')->constrained('listings');

            $table->enum('status', ['pending', 'approved', 'rejected', 'tenanted'])->default('pending');

            $table->string('status_reason')->nullable();
            $table->string('notes')->nullable();

            $table->date('move_in_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
