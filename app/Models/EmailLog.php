<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';

    protected $fillable = [
        'user_id',
        'bursary_application_id',
        'tracking_id',
        'status',
        'email_type',
        'mailable',
        'subject',
        'primary_recipient_email',
        'primary_recipient_name',
        'from',
        'to',
        'cc',
        'bcc',
        'reply_to',
        'attachments',
        'company_name',
        'bursary_title',
        'applicant_name',
        'applicant_email',
        'message_id',
        'transport_message_id',
        'html_body',
        'text_body',
        'last_error',
        'open_count',
        'first_opened_at',
        'last_opened_at',
        'last_open_ip_address',
        'last_open_user_agent',
        'metadata',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'from' => 'array',
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'reply_to' => 'array',
            'attachments' => 'array',
            'metadata' => 'array',
            'first_opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function bursaryApplication(): BelongsTo
    {
        return $this->belongsTo(BursaryApplication::class, 'bursary_application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Sent',
            'failed' => 'Failed',
            default => 'Sending',
        };
    }

    public function hasBeenOpened(): bool
    {
        return (int) $this->open_count > 0 || $this->first_opened_at !== null;
    }
}
