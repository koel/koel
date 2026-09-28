<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeRequested extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $newEmail,
    ) {}

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.email-change-requested', with: [
            'user' => $this->user,
            'newEmail' => $this->newEmail,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your email address is being changed');
    }
}
