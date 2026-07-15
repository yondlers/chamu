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
        Schema::create('lease_versions', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true);

            $table->foreignId('lease_id')->nullable()->constrained('leases')->onDelete('cascade');

            $table->unsignedBigInteger('lease_template_id')->nullable(); // Optional template
            $table->unsignedBigInteger('signature_authorization_id')->nullable(); // Signatory details

            $table->integer('debit_date')->check('debit_date >= 1 AND debit_date <= 31'); //Which day of the time of the mont, 1st, 15th, 25th, 31st
            $table->decimal('rent_amount', 10, 2)->nullable(); // Rent amount

            $table->enum('type', ['amendment', 'extension'])->nullable(); // Type of version

            $table->string('name'); // Name/description of the version

            $table->string('url_code')->unique()->nullable(); // Unique URL for sharing/signature

            //ToDo:: Landlord must decide to upload a signed contract or allow our system to work
            //Incase tenant or landlord upload signed contract
            //Keep in this order,
            $table->string('file_prefix')->unique()->nullable();
            $table->string('lease_contract_file')->unique()->nullable();
            //Incase Tenant signed online
            //Keep in this order,
            $table->datetime('tenant_signed_at')->nullable();
            $table->string('tenant_signed_ip_address')->nullable();
            $table->string('tenant_signed_device')->nullable();

            $table->longText('contract')->nullable();

            $table->date('start_date')->nullable(); // Start date for the version
            $table->date('end_date')->nullable(); // End date for the version

            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_versions');
    }
};
