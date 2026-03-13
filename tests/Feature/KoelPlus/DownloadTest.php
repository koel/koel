<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use App\Services\DownloadService;
use App\Values\Downloadable;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class DownloadTest extends PlusTestCase
{
    #[Test]
    public function downloadPolicy(): void
    {
        $owner = create_user();
        $apiToken = $owner->createToken('Koel')->plainTextToken;

        // Can't download a private song that doesn't belong to the user
        $externalPrivateSong = Song::factory()->private()->createOne();
        $this->get("download/songs?songs[]={$externalPrivateSong->id}&api_token=" . $apiToken)->assertForbidden();

        // Can download a public song that doesn't belong to the user
        $externalPublicSong = Song::factory()->public()->createOne();

        $downloadService = $this->mock(DownloadService::class);
        $downloadService->expects('getDownloadable')->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get("download/songs?songs[]={$externalPublicSong->id}&api_token=" . $apiToken)->assertOk();

        // Can download a private song that belongs to the user
        $ownSong = Song::factory()
            ->for($owner, 'owner')
            ->private()
            ->createOne();
        $downloadService->expects('getDownloadable')->andReturn(Downloadable::make(test_path('songs/blank.mp3')));
        $this->get("download/songs?songs[]={$ownSong->id}&api_token=" . $apiToken)->assertOk();
    }
}
