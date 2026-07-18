<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeToChamu extends Mailable
{
    use Queueable, SerializesModels;

    public string $accountType;

    public function __construct(public string $firstName, string $accountType = 'pupil')
    {
        $this->firstName = trim($this->firstName) !== '' ? trim($this->firstName) : 'there';
        $this->accountType = strtolower(trim($accountType)) === 'student' ? 'student' : 'pupil';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Chamu',
        );
    }

    public function content(): Content
    {
        $baseUrl = rtrim((string) config('app.url', 'https://chamu.co.za'), '/');
        $isStudent = $this->accountType === 'student';

        return new Content(
            view: 'emails.welcome-to-chamu',
            with: [
                'firstName' => $this->firstName,
                'accountType' => $this->accountType,
                'isStudent' => $isStudent,
                'homeUrl' => $baseUrl.'/',
                'fundingUrl' => $baseUrl.'/bursaries',
                'primaryActionUrl' => $isStudent ? $baseUrl.'/bursaries' : $baseUrl.'/',
                'primaryActionLabel' => $isStudent ? 'Explore Funding' : 'Get Started',
            ],
        );
    }
}
