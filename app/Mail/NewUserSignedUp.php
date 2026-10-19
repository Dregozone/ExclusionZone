<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewUserSignedUp extends Mailable
{
    public function __construct(
        public string $userName,
        public string $userEmail,
        public CarbonInterface $signedUpAt,
        public int $totalPlayers,
        public ?string $referrer = null,
        public ?string $country = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Exclusion Zone player: {$this->userName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-user-signed-up',
            with: [
                'time' => $this->signedUpAt->setTimezone('Europe/London')->format('D j M Y, H:i T'),
            ],
        );
    }
}
