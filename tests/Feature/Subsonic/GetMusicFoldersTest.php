<?php

namespace Tests\Feature\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetMusicFoldersTest extends TestCase
{
    #[Test]
    public function returnsSingleMusicFolder(): void
    {
        $user = create_user();

        $this
            ->getJson('/rest/getMusicFolders.view?apiKey=' . $user->subsonic_api_key . '&f=json')
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.musicFolders.musicFolder.0.id', 1)
            ->assertJsonPath('subsonic-response.musicFolders.musicFolder.0.name', 'Music');
    }
}
