<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Models\Song;
use App\Services\LibrarySummaryService;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class LibrarySummaryServiceTest extends PlusTestCase
{
    #[Test]
    public function countsFollowThePublicMediaPreference(): void
    {
        $service = app(LibrarySummaryService::class);
        $viewer = create_user();
        Song::factory()->for(create_user(), 'owner')->public()->createOne();

        $viewer->preferences->includePublicMedia = true;
        $viewer->save();

        self::assertSame(1, $service->getSongCount($viewer));
        self::assertSame(1, $service->getAlbumCount($viewer));
        self::assertSame(1, $service->getArtistCount($viewer));

        $viewer->preferences->includePublicMedia = false;
        $viewer->save();

        self::assertSame(0, $service->getSongCount($viewer));
        self::assertSame(0, $service->getAlbumCount($viewer));
        self::assertSame(0, $service->getArtistCount($viewer));
    }
}
