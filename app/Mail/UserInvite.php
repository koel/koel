<?php

namespace App\Mail;

use App\Hooks\Filter;
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

    public function __construct(
        private readonly User $invitee,
    ) {}

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.invite', with: [
            'invitee' => $this->invitee,
            'url' => apply_filters(
                Filter::INVITATION_URL,
                client_url("invitation/accept/{$this->invitee->invitation_token}"),
                $this->invitee,
            ),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation to join Koel');
    }
}
