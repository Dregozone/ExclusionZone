<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewUserSignUpBurst extends Mailable
{
    public function __construct(
        public int $threshold,
        public int $windowMinutes,
        public int $totalPlayers,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exclusion Zone: burst of new players',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-user-sign-up-burst');
    }
}
