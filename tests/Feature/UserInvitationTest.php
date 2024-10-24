<?php

namespace Tests\Feature;

use App\Http\Resources\UserProspectResource;
use App\Mail\UserInvite;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class UserInvitationTest extends TestCase
{
    #[Test]
    public function invite(): void
    {
        Mail::fake();

        $this->postAs('api/invitations', [
            'emails' => ['foo@bar.io', 'bar@baz.ai'],
            'is_admin' => true,
        ], create_admin())
            ->assertSuccessful()
            ->assertJsonStructure(['*' => UserProspectResource::JSON_STRUCTURE]);

        Mail::assertQueued(UserInvite::class, 2);
    }

    #[Test]
    public function nonAdminCannotInvite(): void
    {
        Mail::fake();

        $this->postAs('api/invitations', ['emails' => ['foo@bar.io', 'bar@baz.ai']])
            ->assertForbidden();

        Mail::assertNothingQueued();
    }

    #[Test]
    public function getProspect(): void
    {
        $prospect = self::createProspect();

        $this->get("api/invitations?token=$prospect->invitation_token")
            ->assertSuccessful()
            ->assertJsonStructure(UserProspectResource::JSON_STRUCTURE);
    }

    #[Test]
    public function revoke(): void
    {
        $prospect = self::createProspect();

        $this->deleteAs('api/invitations', ['email' => $prospect->email], create_admin())
            ->assertSuccessful();

        self::assertModelMissing($prospect);
    }

    #[Test]
    public function nonAdminCannotRevoke(): void
    {
        $prospect = self::createProspect();

        $this->deleteAs('api/invitations', ['email' => $prospect->email])
            ->assertForbidden();

        self::assertModelExists($prospect);
    }

    #[Test]
    public function accept(): void
    {
        $prospect = self::createProspect();

        $this->post('api/invitations/accept', [
            'token' => $prospect->invitation_token,
            'name' => 'Bruce Dickinson',
            'password' => 'SuperSecretPassword',
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['token', 'audio-token']);

        $prospect->refresh();

        self::assertFalse($prospect->is_prospect);
    }

    private static function createProspect(): User
    {
        return User::factory()->for(create_admin(), 'invitedBy')->create([
            'invitation_token' => Str::uuid()->toString(),
            'invited_at' => now(),
        ]);
    }
}
