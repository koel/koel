<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmEmailChange extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $newEmail,
        public readonly string $confirmationUrl,
    ) {}

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.confirm-email-change', with: [
            'user' => $this->user,
            'newEmail' => $this->newEmail,
            'url' => $this->confirmationUrl,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your new email address');
    }
}
