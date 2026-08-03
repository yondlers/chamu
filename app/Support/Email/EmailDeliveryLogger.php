<?php

namespace App\Support\Email;

use App\Mail\BursaryApplicationReceipt;
use App\Mail\BursaryApplicationSubmitted;
use App\Mail\WelcomeToChamu;
use App\Models\AuditLog;
use App\Models\BursaryApplication;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class EmailDeliveryLogger
{
    private const TRACKING_HEADER = 'X-Chamu-Email-Log-Id';

    public function recordSending(MessageSending $event): void
    {
        $message = $event->message;
        $archiveBccAdded = $this->addArchiveBcc($message);

        if (! Schema::hasTable('email_logs')) {
            return;
        }

        $trackingId = (string) Str::uuid();
        $message->getHeaders()->addTextHeader(self::TRACKING_HEADER, $trackingId);
        $openTrackingAdded = $this->addOpenTrackingPixel($message, $trackingId);
        $context = $this->contextFromData($event->data);
        $to = $this->addressesToArray($message->getTo());
        $primaryRecipient = $to[0] ?? null;
        $user = $context['application']?->user
            ?? $this->userForRecipient($primaryRecipient['email'] ?? null);

        EmailLog::create([
            'user_id' => $context['application']?->user_id ?? $user?->id,
            'bursary_application_id' => $context['application']?->id,
            'tracking_id' => $trackingId,
            'status' => 'sending',
            'email_type' => $context['email_type'],
            'mailable' => $context['mailable'],
            'subject' => $message->getSubject(),
            'primary_recipient_email' => $primaryRecipient['email'] ?? null,
            'primary_recipient_name' => $primaryRecipient['name'] ?? null,
            'from' => $this->addressesToArray($message->getFrom()),
            'to' => $to,
            'cc' => $this->addressesToArray($message->getCc()),
            'bcc' => $this->addressesToArray($message->getBcc()),
            'reply_to' => $this->addressesToArray($message->getReplyTo()),
            'attachments' => $this->attachmentsToArray($message),
            'company_name' => $context['company_name'],
            'bursary_title' => $context['bursary_title'],
            'applicant_name' => $context['applicant_name'],
            'applicant_email' => $context['applicant_email'],
            'message_id' => $this->messageId($message),
            'html_body' => $this->htmlBody($message),
            'text_body' => $this->textBody($message),
            'metadata' => [
                'archive_bcc_added' => $archiveBccAdded,
                'open_tracking_added' => $openTrackingAdded,
                'view_data_keys' => array_values(array_filter(array_keys($event->data), fn (string $key): bool => ! str_starts_with($key, '__'))),
            ],
        ]);
    }

    public function recordSent(MessageSent $event): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        $message = $event->message;
        $trackingId = $this->trackingId($message);
        $context = $this->contextFromData($event->data);
        $log = $trackingId
            ? EmailLog::where('tracking_id', $trackingId)->first()
            : null;

        if (! $log) {
            $to = $this->addressesToArray($message->getTo());
            $primaryRecipient = $to[0] ?? null;
            $log = EmailLog::create([
                'user_id' => $context['application']?->user_id ?? $this->userForRecipient($primaryRecipient['email'] ?? null)?->id,
                'bursary_application_id' => $context['application']?->id,
                'tracking_id' => $trackingId ?: (string) Str::uuid(),
                'status' => 'sending',
                'email_type' => $context['email_type'],
                'mailable' => $context['mailable'],
                'subject' => $message->getSubject(),
                'primary_recipient_email' => $primaryRecipient['email'] ?? null,
                'primary_recipient_name' => $primaryRecipient['name'] ?? null,
                'from' => $this->addressesToArray($message->getFrom()),
                'to' => $to,
                'cc' => $this->addressesToArray($message->getCc()),
                'bcc' => $this->addressesToArray($message->getBcc()),
                'reply_to' => $this->addressesToArray($message->getReplyTo()),
                'attachments' => $this->attachmentsToArray($message),
                'company_name' => $context['company_name'],
                'bursary_title' => $context['bursary_title'],
                'applicant_name' => $context['applicant_name'],
                'applicant_email' => $context['applicant_email'],
                'message_id' => $this->messageId($message),
                'html_body' => $this->htmlBody($message),
                'text_body' => $this->textBody($message),
            ]);
        }

        $log->forceFill([
            'status' => 'sent',
            'message_id' => $log->message_id ?: $this->messageId($message),
            'transport_message_id' => $this->transportMessageId($event),
            'sent_at' => now(),
            'failed_at' => null,
            'last_error' => null,
        ])->save();

        $this->auditApplicationEmail($log, 'sent');
    }

    public static function markFailed(?string $recipientEmail, ?string $mailable, ?BursaryApplication $application, Throwable $exception): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        $logger = app(self::class);
        $log = EmailLog::where('status', 'sending')
            ->when($recipientEmail, fn ($query) => $query->where('primary_recipient_email', $recipientEmail))
            ->when($mailable, fn ($query) => $query->where('mailable', $mailable))
            ->when($application, fn ($query) => $query->where('bursary_application_id', $application->id))
            ->latest()
            ->first();

        if (! $log) {
            $application?->loadMissing(['bursary.company', 'user']);
            $applicationMetadata = $application?->metadata ?? [];
            $log = EmailLog::create([
                'user_id' => $application?->user_id,
                'bursary_application_id' => $application?->id,
                'tracking_id' => (string) Str::uuid(),
                'status' => 'sending',
                'email_type' => $logger->emailTypeForMailable($mailable),
                'mailable' => $mailable,
                'primary_recipient_email' => $recipientEmail,
                'to' => $recipientEmail ? [['email' => $recipientEmail, 'name' => null]] : null,
                'company_name' => $application?->bursary?->company?->name ?? $applicationMetadata['company_name'] ?? null,
                'bursary_title' => $application?->bursary?->title ?? $applicationMetadata['bursary_title'] ?? null,
                'applicant_name' => $application?->applicant_name,
                'applicant_email' => $application?->applicant_email,
            ]);
        }

        $log->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'last_error' => $exception->getMessage(),
        ])->save();

        $logger->auditApplicationEmail($log, 'failed');
    }

    private function contextFromData(array $data): array
    {
        $mailable = $data['__laravel_mailable'] ?? null;
        $application = ($data['application'] ?? null) instanceof BursaryApplication
            ? $data['application']
            : null;

        $application?->loadMissing(['bursary.company', 'user']);
        $applicationMetadata = $application?->metadata ?? [];

        return [
            'application' => $application,
            'mailable' => $mailable,
            'email_type' => $this->emailTypeForMailable($mailable),
            'company_name' => $application?->bursary?->company?->name ?? $applicationMetadata['company_name'] ?? null,
            'bursary_title' => $application?->bursary?->title ?? $applicationMetadata['bursary_title'] ?? null,
            'applicant_name' => $application?->applicant_name,
            'applicant_email' => $application?->applicant_email,
        ];
    }

    private function emailTypeForMailable(?string $mailable): ?string
    {
        return match ($mailable) {
            BursaryApplicationSubmitted::class => 'bursary_application_provider',
            BursaryApplicationReceipt::class => 'bursary_application_receipt',
            WelcomeToChamu::class => 'welcome',
            default => $mailable ? Str::of(class_basename($mailable))->snake()->toString() : null,
        };
    }

    private function addArchiveBcc(Email $message): bool
    {
        $archiveAddress = trim((string) config('mail.archive.address', ''));

        if (! filter_var($archiveAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $allRecipients = collect([
            ...$this->addressesToArray($message->getTo()),
            ...$this->addressesToArray($message->getCc()),
            ...$this->addressesToArray($message->getBcc()),
        ])->pluck('email')->filter()->map(fn (string $email): string => strtolower($email));

        if ($allRecipients->contains(strtolower($archiveAddress))) {
            return false;
        }

        $archiveName = trim((string) config('mail.archive.name', 'Chamu Email Archive'));
        $message->addBcc(new Address($archiveAddress, $archiveName));

        return true;
    }

    private function addOpenTrackingPixel(Email $message, string $trackingId): bool
    {
        $html = $this->htmlBody($message);

        if (! $html) {
            return false;
        }

        $pixelUrl = route('emails.open', ['trackingId' => $trackingId]);
        $pixel = '<img src="'.e($pixelUrl).'" width="1" height="1" alt="" style="display:none;max-width:1px;max-height:1px;opacity:0;border:0;">';

        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $pixel.'</body>', $html);
        } else {
            $html .= $pixel;
        }

        $message->html($html);

        return true;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{email: string, name: ?string}>
     */
    private function addressesToArray(array $addresses): array
    {
        return collect($addresses)
            ->map(fn (Address $address): array => [
                'email' => $address->getAddress(),
                'name' => trim($address->getName()) !== '' ? $address->getName() : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function attachmentsToArray(Email $message): array
    {
        return collect($message->getAttachments())
            ->map(fn ($attachment): array => [
                'filename' => method_exists($attachment, 'getFilename') ? $attachment->getFilename() : null,
                'media_type' => method_exists($attachment, 'getMediaType') ? $attachment->getMediaType() : null,
                'media_subtype' => method_exists($attachment, 'getMediaSubtype') ? $attachment->getMediaSubtype() : null,
            ])
            ->values()
            ->all();
    }

    private function userForRecipient(?string $email): ?User
    {
        if (! $email) {
            return null;
        }

        return User::where('email', $email)->first();
    }

    private function trackingId(Email $message): ?string
    {
        $header = $message->getHeaders()->get(self::TRACKING_HEADER);

        return $header ? trim((string) $header->getBodyAsString()) : null;
    }

    private function messageId(Email $message): ?string
    {
        try {
            return $message->getMessageId();
        } catch (Throwable) {
            return null;
        }
    }

    private function transportMessageId(MessageSent $event): ?string
    {
        try {
            return $event->sent->getMessageId();
        } catch (Throwable) {
            return null;
        }
    }

    private function htmlBody(Email $message): ?string
    {
        try {
            return $message->getHtmlBody();
        } catch (Throwable) {
            return null;
        }
    }

    private function textBody(Email $message): ?string
    {
        try {
            return $message->getTextBody();
        } catch (Throwable) {
            return null;
        }
    }

    private function auditApplicationEmail(EmailLog $log, string $status): void
    {
        if (! Schema::hasTable('audit_logs') || ! $log->bursary_application_id) {
            return;
        }

        AuditLog::create([
            'user_id' => $log->user_id,
            'name' => $status === 'sent' ? 'Bursary application email sent' : 'Bursary application email failed',
            'event' => $status === 'sent' ? 'bursary_application.email_sent' : 'bursary_application.email_failed',
            'auditable_type' => BursaryApplication::class,
            'auditable_id' => $log->bursary_application_id,
            'description' => trim(($log->subject ?? 'Application email').' - '.$log->primary_recipient_email),
            'metadata' => [
                'email_log_id' => $log->id,
                'status' => $log->status,
                'email_type' => $log->email_type,
                'to' => $log->to,
                'company_name' => $log->company_name,
                'bursary_title' => $log->bursary_title,
                'applicant_name' => $log->applicant_name,
                'applicant_email' => $log->applicant_email,
                'last_error' => $log->last_error,
            ],
        ]);
    }
}
