<?php

namespace Tests\Feature\Subsonic;

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
            ->getJson("/rest/getUser.view?apiKey={$user->subsonic_api_key}&f=json&username=" . urlencode($user->name))
            ->assertOk()
            ->assertJsonPath('subsonic-response.user.username', $user->name)
            ->assertJsonPath('subsonic-response.user.email', $user->email)
            ->assertJsonPath('subsonic-response.user.adminRole', false);
    }

    #[Test]
    public function adminCanQueryOtherUsers(): void
    {
        $admin = create_admin();
        $other = create_user();

        $this
            ->getJson("/rest/getUser.view?apiKey={$admin->subsonic_api_key}&f=json&username=" . urlencode($other->name))
            ->assertOk()
            ->assertJsonPath('subsonic-response.user.username', $other->name);
    }

    #[Test]
    public function nonAdminQueryingAnotherUserReturnsCode50(): void
    {
        $alice = create_user();
        $bob = create_user();

        $this
            ->getJson("/rest/getUser.view?apiKey={$alice->subsonic_api_key}&f=json&username=" . urlencode($bob->name))
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
