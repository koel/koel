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
    public function songCountFollowsThePublicMediaPreference(): void
    {
        $service = app(LibrarySummaryService::class);
        $viewer = create_user();
        Song::factory()->for(create_user(), 'owner')->public()->createOne();

        $viewer->preferences->includePublicMedia = true;
        $viewer->save();

        self::assertSame(1, $service->getSongCount($viewer));

        $viewer->preferences->includePublicMedia = false;
        $viewer->save();

        self::assertSame(0, $service->getSongCount($viewer));
    }
}
