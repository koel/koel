<?php

namespace App\Mail;

use App\Hooks\Filter;
use App\Models\User;
use App\Services\SettingService;
use App\Values\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class UserInvite extends Mailable
{
    use Queueable;
    use SerializesModels;

    private Branding $branding;

    public function __construct(
        private readonly User $invitee,
    ) {}

    public function build(SettingService $settingService): void
    {
        $this->branding = $settingService->getBranding($this->invitee->organization);
        $this->subject("Invitation to join {$this->branding->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.invite', with: [
            'branding' => $this->branding,
            'invitee' => $this->invitee,
            'url' => apply_filters(
                Filter::INVITATION_URL,
                client_url("invitation/accept/{$this->invitee->invitation_token}"),
                $this->invitee,
            ),
        ]);
    }
}
