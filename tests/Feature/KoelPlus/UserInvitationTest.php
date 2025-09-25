<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\UserProspectResource;
use App\Mail\UserInvite;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;

class UserInvitationTest extends PlusTestCase
{
    #[Test]
    public function canInviteRolesAvailableInCommunityEdition(): void
    {
        Mail::fake();

        $this->postAs('api/invitations', [
            'emails' => ['foo@bar.io', 'bar@baz.ai'],
            'role' => 'manager',
        ], create_admin())
            ->assertSuccessful()
            ->assertJsonStructure([0 => UserProspectResource::JSON_STRUCTURE]);

        Mail::assertQueued(UserInvite::class, 2);
    }
}
