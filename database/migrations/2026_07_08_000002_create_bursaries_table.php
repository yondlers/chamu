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
        Schema::create('bursaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->text('summary')->nullable();
            $table->text('fields_covered')->nullable();
            $table->text('coverage_value')->nullable();
            $table->text('service_contract')->nullable();
            $table->text('renewal')->nullable();
            $table->json('eligibility_requirements')->nullable();
            $table->text('application_method')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->date('closing_date')->nullable();
            $table->string('closing_date_label')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('source_url')->unique();
            $table->string('apply_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('closing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bursaries');
    }
};
