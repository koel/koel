<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\UserResource;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class GetUserTest extends TestCase
{
    #[Test]
    public function returnsSelf(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getUser.view?apiKey={$user->subsonic_api_key}&f=json&username=" . urlencode($user->email))
            ->assertOk()
            ->assertJsonStructure(['subsonic-response' => ['user' => UserResource::JSON_STRUCTURE]])
            ->assertJsonPath('subsonic-response.user.username', $user->email)
            ->assertJsonPath('subsonic-response.user.adminRole', false);
    }

    #[Test]
    public function adminCanQueryOtherUsers(): void
    {
        $admin = create_admin();
        $other = create_user();

        $this
            ->getJson(
                "/rest/getUser.view?apiKey={$admin->subsonic_api_key}&f=json&username=" . urlencode($other->email),
            )
            ->assertOk()
            ->assertJsonStructure(['subsonic-response' => ['user' => UserResource::JSON_STRUCTURE]])
            ->assertJsonPath('subsonic-response.user.username', $other->email);
    }

    #[Test]
    public function nonAdminQueryingAnotherUserReturnsCode50(): void
    {
        $alice = create_user();
        $bob = create_user();

        $this
            ->getJson("/rest/getUser.view?apiKey={$alice->subsonic_api_key}&f=json&username=" . urlencode($bob->email))
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 50);
    }

    #[Test]
    public function missingUsernameReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getUser.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
