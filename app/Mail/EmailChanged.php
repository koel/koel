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

class EmailChanged extends Mailable
{
    use Queueable;
    use SerializesModels;

    private Branding $branding;

    public function __construct(
        public readonly User $user,
        public readonly string $previousEmail,
        public readonly string $newEmail,
    ) {}

    public function build(SettingService $settingService): void
    {
        $this->branding = $settingService->getBranding($this->user->organization);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.email-changed', with: [
            'branding' => $this->branding,
            'user' => $this->user,
            'previousEmail' => $this->previousEmail,
            'newEmail' => $this->newEmail,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your email address was changed');
    }
}
