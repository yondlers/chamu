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
        Schema::create('leases', function (Blueprint $table) {
            $table->id();

            $table->boolean('active')->default(true); // Active/inactive lease

            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('lease_template_id')->nullable()->constrained('lease_templates')->onDelete('cascade');

            $table->integer('late_fee')->nullable();
            $table->integer('late_fee_days')->nullable();//number of days late

            $table->enum('utility_payer', ['tenant', 'owner'])->default('tenant')->nullable();
            $table->integer('notice_period')->nullable();//this is number of days

            $table->integer('debit_date')->check('debit_date BETWEEN 1 AND 31');

            $table->decimal('rent_amount', 10, 2)->nullable(); // Rent amount

            $table->enum('type', ['initial', 'amendment', 'extension'])->default('initial'); // Type of lease

            $table->string('name'); // Lease/contract name or title

            $table->string('url_code')->unique()->nullable(); // Unique URL for sharing/signature

            //ToDo:: Landlord must decide to upload a signed contract or allow our system to work
            //Incase tenant or landlord upload signed contract
            //Keep in this order,
            $table->string('file_prefix')->unique()->nullable();
            $table->string('lease_contract_file')->unique()->nullable();


            //Incase Tenant signed online
            //Keep in this order,
            $table->longText('tenant_signature')->nullable();
            $table->datetime('tenant_signed_at')->nullable();
            $table->string('tenant_signed_ip_address')->nullable();
            $table->string('tenant_signed_device')->nullable();

            $table->longText('contract')->nullable(); //Fully Signed,

            $table->date('start_date')->nullable(); // Start of the lease
            $table->date('end_date')->nullable(); // End of the lease

            $table->foreignId('signature_authorization_id')->nullable()->constrained('signature_authorizations')->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
