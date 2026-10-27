<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Genre;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Services\Podcast\PodcastService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_guest;
use function Tests\create_user;

class GuestTest extends PlusTestCase
{
    #[Test]
    public function seePublicMediaEvenWhenExcludedInPreferences(): void
    {
        $guest = create_guest();
        $guest->preferences->includePublicMedia = false;
        $guest->save();

        $genre = Genre::factory()->createOne(['name' => 'Rock']);
        Song::factory()->for(create_user(), 'owner')->public()->hasAttached($genre)->createOne(['length' => 20]);

        $this->getAs('api/genres', $guest)->assertJsonFragment(['name' => 'Rock', 'song_count' => 1]);
    }

    #[Test]
    public function cannotSubscribeToPodcasts(): void
    {
        $this->mock(PodcastService::class)->expects('addPodcast')->never();

        $this->postAs('api/podcasts', ['url' => 'https://example.com/feed.xml'], create_guest())->assertForbidden();

        $this->assertDatabaseEmpty(Podcast::class);
    }

    #[Test]
    public function cannotAddRadioStations(): void
    {
        Http::fake(['*' => Http::response('', 200, ['Content-Type' => 'audio/mpeg'])]);

        $this->postAs(
            'api/radio/stations',
            [
                'url' => 'https://example.com/stream',
                'name' => 'Guest Radio',
            ],
            create_guest(),
        )->assertForbidden();

        $this->assertDatabaseEmpty(RadioStation::class);
    }
}
