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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);

            $table->decimal('amount', 10, 2); // Amount of the invoice
            $table->decimal('balance', 10, 2);

            $table->string('name');
            $table->string('account_type'); // ['payment', 'rent charges', 'rent discount', 'other'] // Account type
            $table->string('description')->nullable();
            $table->string('transaction_id'); // Unique transaction ID

            $table->enum('type', ['Debit', 'Credit']); // Debit or Credit

            $table->date('invoice_date')->nullable(); // Date of the invoice

            $table->text('notes')->nullable(); // Optional notes
            $table->string('document_id')->nullable();

            // Add foreign key constraints
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
        Schema::dropIfExists('invoices');
    }
};
