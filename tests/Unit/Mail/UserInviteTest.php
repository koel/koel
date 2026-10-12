<?php

namespace Tests\Unit\Mail;

use App\Mail\UserInvite;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserInviteTest extends TestCase
{
    #[Test]
    public function invitationLinkPointsToAppUrlWhateverTheRequestHost(): void
    {
        config(['app.url' => 'https://music.example.com']);
        URL::forceRootUrl('http://evil.example');

        $invitee = User::factory()->prospect()->createOne(['invitation_token' => 'invitation-token']);

        (new UserInvite($invitee))->assertSeeInHtml(
            'https://music.example.com/#/invitation/accept/invitation-token',
            escape: false,
        );
    }

    #[Test]
    public function invitationLinkUsesACleanUrlWhenEnabled(): void
    {
        config(['app.url' => 'https://music.example.com', 'koel.clean_urls.enabled' => true]);

        $invitee = User::factory()->prospect()->createOne(['invitation_token' => 'invitation-token']);

        (new UserInvite($invitee))->assertSeeInHtml(
            'https://music.example.com/invitation/accept/invitation-token',
            escape: false,
        );
    }
}
