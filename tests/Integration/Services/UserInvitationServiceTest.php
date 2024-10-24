<?php

namespace Tests\Integration\Services;

use App\Exceptions\InvitationNotFoundException;
use App\Mail\UserInvite;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class UserInvitationServiceTest extends TestCase
{
    private UserInvitationService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserInvitationService::class);
    }

    #[Test]
    public function invite(): void
    {
        Mail::fake();

        $emails = ['foo@bar.com', 'bar@baz.io'];
        $user = create_admin();

        $this->service
            ->invite($emails, true, $user)
            ->each(static function (User $prospect) use ($user): void {
                self::assertTrue($prospect->is_admin);
                self::assertTrue($prospect->invitedBy->is($user));
                self::assertTrue($prospect->is_prospect);
                self::assertNotNull($prospect->invitation_token);
                self::assertNotNull($prospect->invited_at);
                self::assertNull($prospect->invitation_accepted_at);
            });

        Mail::assertQueued(UserInvite::class, 2);
    }

    #[Test]
    public function getUserProspectByToken(): void
    {
        $token = Str::uuid()->toString();
        $user = create_admin();

        $prospect = User::factory()->for($user, 'invitedBy')->create([
            'invitation_token' => $token,
            'invited_at' => now(),
        ]);

        self::assertTrue($this->service->getUserProspectByToken($token)->is($prospect));
    }

    #[Test]
    public function getUserProspectByTokenThrowsIfTokenNotFound(): void
    {
        $this->expectException(InvitationNotFoundException::class);
        $this->service->getUserProspectByToken(Str::uuid()->toString());
    }

    #[Test]
    public function revokeByEmail(): void
    {
        $user = create_admin();

        /** @var User $prospect */
        $prospect = User::factory()->for($user, 'invitedBy')->create([
            'invitation_token' => Str::uuid()->toString(),
            'invited_at' => now(),
        ]);

        $this->service->revokeByEmail($prospect->email);

        self::assertModelMissing($prospect);
    }

    #[Test]
    public function accept(): void
    {
        $token = Str::uuid()->toString();
        $user = create_admin();

        User::factory()->for($user, 'invitedBy')->create([
            'invitation_token' => $token,
            'invited_at' => now(),
            'is_admin' => true,
        ]);

        $user = $this->service->accept($token, 'Bruce Dickinson', 'SuperSecretPassword');

        self::assertFalse($user->is_prospect);
        self::assertTrue($user->is_admin);
        self::assertNull($user->invitation_token);
        self::assertNotNull($user->invitation_accepted_at);
        self::assertTrue(Hash::check('SuperSecretPassword', $user->password));
    }
}
