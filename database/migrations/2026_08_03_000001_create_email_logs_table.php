<?php

use App\Models\BursaryApplication;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(BursaryApplication::class)->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_id')->unique();
            $table->string('status')->default('sending')->index();
            $table->string('email_type')->nullable()->index();
            $table->string('mailable')->nullable()->index();
            $table->text('subject')->nullable();
            $table->string('primary_recipient_email')->nullable()->index();
            $table->string('primary_recipient_name')->nullable();
            $table->json('from')->nullable();
            $table->json('to')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->json('reply_to')->nullable();
            $table->json('attachments')->nullable();
            $table->string('company_name')->nullable()->index();
            $table->string('bursary_title')->nullable()->index();
            $table->string('applicant_name')->nullable()->index();
            $table->string('applicant_email')->nullable()->index();
            $table->string('message_id')->nullable()->index();
            $table->string('transport_message_id')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
