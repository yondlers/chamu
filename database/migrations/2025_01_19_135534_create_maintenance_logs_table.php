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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);
            $table->string('url_code')->unique()->nullable(); // Unique URL

            // Additional fields
            $table->text('name');    // Notes about the maintenance
            $table->text('description')->nullable();    // Notes about the maintenance

            $table->decimal('material_cost', 10, 2)->nullable(); // Cost of the maintenance
            $table->decimal('labour_cost', 10, 2)->nullable(); // Cost of the maintenance
            $table->decimal('total_cost', 10, 2)->nullable(); // Cost of the maintenance

            $table->longText('before_image')->nullable();
            $table->longText('after_image')->nullable();

            $table->string('performed_by')->nullable(); // Name of the person/company
            $table->date('performed_at')->nullable();   // When it was done

            $table->foreignId('maintenance_type_id')->nullable()->constrained('maintenance_types')->onDelete('cascade'); // FK to teams table
            $table->foreignId('lease_id')->nullable()->constrained('leases')->onDelete('cascade'); // FK to teams table
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade'); // FK to teams table


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
