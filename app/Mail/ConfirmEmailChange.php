<?php

namespace App\Mail;

use App\Models\User;
use App\Services\SettingService;
use App\Values\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmEmailChange extends Mailable
{
    use Queueable;
    use SerializesModels;

    private Branding $branding;

    public function __construct(
        public readonly User $user,
        public readonly string $newEmail,
        public readonly string $confirmationUrl,
    ) {}

    public function build(SettingService $settingService): void
    {
        $this->branding = $settingService->getBranding($this->user->organization);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.confirm-email-change', with: [
            'branding' => $this->branding,
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
