<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChanged extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $previousEmail,
    ) {}

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.email-changed', with: [
            'user' => $this->user,
            'previousEmail' => $this->previousEmail,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your email address was changed');
    }
}
