<?php

namespace Tests\Feature\Subsonic;

use App\Models\Organization;
use App\Models\User;
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

    #[Test]
    public function userOfAnotherOrganizationReturnsCode70(): void
    {
        $filename = 'sub_avatar_outsider_test.png';
        $path = image_storage_path($filename);
        File::copy(test_path('fixtures/cover.png'), $path);
        $this->createdPaths[] = $path;

        $user = create_user();
        $outsider = User::factory()->for(Organization::factory()->createOne())->createOne(['avatar' => $filename]);

        $this
            ->getJson(
                "/rest/getAvatar.view?apiKey={$user->subsonic_api_key}&f=json&username=" . urlencode($outsider->email),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
