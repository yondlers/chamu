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
        Schema::create('check_in_outs', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);

            $table->string('name'); // Name of the check-in/out item
            $table->text('initial_checkin_list'); // Initial check-in list
            $table->text('tenant_checkin_list')->nullable(); // Final check-in list
            $table->text('tenant_checkout_list')->nullable(); // Final check-out list

            $table->boolean('requires_checkout')->default(false); // Indicates if checkout is required

            $table->foreignId('lease_id')->nullable()->constrained('leases')->onDelete('cascade'); // Related lease
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade'); // Related team

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_outs');
    }
};
