<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\Integrations\ITunesService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ViewSongOnITunesTest extends TestCase
{
    #[Test]
    public function redirectToITunesWithApiTokenInQuery(): void
    {
        $album = Album::factory()->createOne();
        $apiToken = create_user()->createToken('koel')->plainTextToken;

        $this
            ->mock(ITunesService::class)
            ->expects('getTrackUrl')
            ->with('Fear of the Dark', Mockery::on($album->is(...)))
            ->andReturn('https://itunes.apple.com/track');

        $this->get("itunes/song/{$album->id}?q=Fear%20of%20the%20Dark&api_token=$apiToken")->assertRedirect(
            'https://itunes.apple.com/track',
        );
    }
}
