<?php

namespace Tests\Feature\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetOpenSubsonicExtensionsTest extends TestCase
{
    #[Test]
    public function declaresSupportedExtensions(): void
    {
        $user = create_user();

        $response = $this
            ->getJson("/rest/getOpenSubsonicExtensions.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $names = array_column($response->json('subsonic-response.openSubsonicExtensions'), 'name');
        self::assertContains('songLyrics', $names);
        self::assertContains('transcodeOffset', $names);
    }
}
