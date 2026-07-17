<?php

namespace App\Mail;

use App\Models\BursaryApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BursaryApplicationReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BursaryApplication $application)
    {
        $this->application->loadMissing(['bursary.company', 'documents.requirement']);
    }

    public function envelope(): Envelope
    {
        $verb = $this->application->delivery_type === 'postal' ? 'prepared' : 'sent';

        return new Envelope(
            subject: 'Receipt: Chamu '.$verb.' your '.$this->application->bursary->title.' application',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bursaries.application-receipt',
        );
    }
}
