<?php

namespace Tests\Feature\Subsonic;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class GetAvatarTest extends TestCase
{
    /** @var list<string> */
    private array $createdPaths = [];

    protected function tearDown(): void
    {
        File::delete($this->createdPaths);

        parent::tearDown();
    }

    #[Test]
    public function servesAvatarBytesWhenCustomAvatarSet(): void
    {
        $filename = 'sub_avatar_test.png';
        $path = image_storage_path($filename);
        File::copy(test_path('fixtures/cover.png'), $path);
        $this->createdPaths[] = $path;

        $user = create_user(['avatar' => $filename]);

        $response = $this->get(
            "/rest/getAvatar.view?apiKey={$user->subsonic_api_key}&username=" . urlencode($user->email),
        )->assertOk()->assertHeader('Content-Type', 'image/png');

        self::assertSame(File::get($path), $response->streamedContent());
    }

    #[Test]
    public function returnsCode70WhenNoCustomAvatar(): void
    {
        $user = create_user();

        $this
            ->getJson(
                "/rest/getAvatar.view?apiKey={$user->subsonic_api_key}&f=json&username=" . urlencode($user->email),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    #[Test]
    public function unknownUserReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getAvatar.view?apiKey={$user->subsonic_api_key}&f=json&username=ghost")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
