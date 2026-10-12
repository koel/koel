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

class PasswordChanged extends Mailable
{
    use Queueable;
    use SerializesModels;

    private Branding $branding;

    public function __construct(
        public readonly User $user,
    ) {}

    public function build(SettingService $settingService): void
    {
        $this->branding = $settingService->getBranding($this->user->organization);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.users.password-changed', with: [
            'branding' => $this->branding,
            'user' => $this->user,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your password was changed');
    }
}
