<?php

namespace App\Mail;

use App\Models\BursaryApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BursaryApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BursaryApplication $application)
    {
        $this->application->loadMissing(['bursary.company', 'documents.requirement', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address($this->application->applicant_email, $this->application->applicant_name),
            ],
            subject: 'Chamu bursary application: '.$this->application->applicant_name.' for '.$this->application->bursary->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bursaries.application-submitted',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->application->documents
            ->map(fn ($document): Attachment => Attachment::fromStorageDisk($document->storage_disk, $document->path)
                ->as($document->original_name)
                ->withMime($document->mime_type ?: 'application/octet-stream'))
            ->values()
            ->all();
    }
}
