<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvite extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly User $invitee)
    {
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.users.invite',
            with: [
                'invitee' => $this->invitee,
                'url' => url("/#/invitation/accept/{$this->invitee->invitation_token}"),
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation to join Koel');
    }
}
