<?php

namespace Tests\Feature\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetNowPlayingTest extends TestCase
{
    #[Test]
    public function returnsEmptyNowPlaying(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getNowPlaying.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.nowPlaying', []);
    }
}
