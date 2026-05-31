<?php

namespace Tests\Feature;

use App\Services\Subsonic\Authenticators\ApiKeyAuthenticator;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class SubsonicApiKeyTest extends TestCase
{
    #[Test]
    public function meExposesOwnSubsonicApiKey(): void
    {
        $user = create_user();

        $this->getAs('api/me', $user)->assertOk()->assertJsonPath('subsonic_api_key', $user->subsonic_api_key);
    }

    #[Test]
    public function userListingDoesNotLeakOtherUsersKeys(): void
    {
        $admin = create_admin();
        $other = create_user();

        $this
            ->getAs('api/users', $admin)
            ->assertOk()
            ->assertJsonMissing(['subsonic_api_key' => $other->subsonic_api_key]);
    }

    #[Test]
    public function regenerateRotatesAndReturnsNewKey(): void
    {
        $user = create_user();
        $oldKey = $user->subsonic_api_key;

        $response = $this->postAs('api/me/subsonic-api-key/regenerate', [], $user)->assertOk();

        $newKey = $response->json('subsonic_api_key');
        self::assertNotSame($oldKey, $newKey);
        self::assertNotEmpty($newKey);
        self::assertSame($newKey, $user->refresh()->subsonic_api_key);
    }

    #[Test]
    public function keyIsStoredEncryptedWithHashForLookup(): void
    {
        $user = create_user();
        $plaintext = $user->subsonic_api_key;

        $row = DB::table('users')->where('id', $user->id)->first();

        self::assertNotSame($plaintext, $row->subsonic_api_key);
        self::assertSame(app(ApiKeyAuthenticator::class)->hash($plaintext), $row->subsonic_api_key_hash);
    }
}
