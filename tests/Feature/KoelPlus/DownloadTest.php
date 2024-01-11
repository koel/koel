<?php

namespace Tests\Feature\KoelPlus;

use App\Facades\License;
use App\Models\Song;
use App\Services\DownloadService;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class DownloadTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        License::fakePlusLicense();
    }

    public function testDownloadPolicy(): void
    {
        $owner = create_user();
        $apiToken = $owner->createToken('Koel')->plainTextToken;

        // Can't download a private song that doesn't belong to the user
        /** @var Song $externalPrivateSong */
        $externalPrivateSong = Song::factory()->private()->create();
        $this->get("download/songs?songs[]=$externalPrivateSong->id&api_token=" . $apiToken)
            ->assertForbidden();

        // Can download a public song that doesn't belong to the user
        /** @var Song $externalPublicSong */
        $externalPublicSong = Song::factory()->public()->create();

        $downloadService = self::mock(DownloadService::class);
        $downloadService->shouldReceive('getDownloadablePath')
            ->once()
            ->andReturn(test_path('songs/blank.mp3'));

        $this->get("download/songs?songs[]=$externalPublicSong->id&api_token=" . $apiToken)
            ->assertOk();

        // Can download a private song that belongs to the user
        /** @var Song $ownSong */
        $ownSong = Song::factory()->for($owner, 'owner')->private()->create();
        $downloadService->shouldReceive('getDownloadablePath')
            ->once()
            ->andReturn(test_path('songs/blank.mp3'));
        $this->get("download/songs?songs[]=$ownSong->id&api_token=" . $apiToken)
            ->assertOk();
    }
}
