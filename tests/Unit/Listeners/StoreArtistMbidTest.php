<?php

namespace Tests\Unit\Listeners;

use App\Events\ArtistMbidResolved;
use App\Listeners\StoreArtistMbid;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreArtistMbidTest extends TestCase
{
    #[Test]
    public function storeMbid(): void
    {
        $artist = Artist::factory()->createOne();

        (new StoreArtistMbid())->handle(new ArtistMbidResolved($artist, 'sample-mbid'));

        self::assertSame('sample-mbid', $artist->refresh()->mbid);
    }

    #[Test]
    public function keepExistingMbid(): void
    {
        $artist = Artist::factory()->createOne(['mbid' => 'mbid-from-tags']);

        (new StoreArtistMbid())->handle(new ArtistMbidResolved($artist, 'sample-mbid'));

        self::assertSame('mbid-from-tags', $artist->refresh()->mbid);
    }
}
