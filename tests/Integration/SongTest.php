<?php

namespace Tests\Integration;

use App\Models\Song;
use App\Models\User;
use Aws\AwsClient;
use Cache;
use Lastfm;
use Mockery as m;
use Tests\TestCase;
use YouTube;

class SongTest extends TestCase
{
    /** @test */
    public function it_returns_object_storage_public_url()
    {
        // Given there's a song hosted on Amazon S3
        /** @var Song $song */
        $song = factory(Song::class)->create(['path' => 's3://foo/bar']);
        $mockedURL = 'http://aws.com/foo/bar';

        // When I get the song's object storage public URL
        $client = m::mock(AwsClient::class, [
            'getCommand' => null,
            'createPresignedRequest' => m::mock(Request::class, [
                'getUri' => $mockedURL,
            ]),
        ]);

        Cache::shouldReceive('remember')->andReturn($mockedURL);
        $url = $song->getObjectStoragePublicUrl($client);

        // Then I should receive the correct S3 public URL
        $this->assertEquals($mockedURL, $url);
    }

    /** @test */
    public function it_scrobbles_if_the_user_is_connected_to_lastfm()
    {
        // Given there's a song
        /** @var Song $song */
        $song = factory(Song::class)->create();

        // And a user who's connected to lastfm
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->setPreference('lastfm_session_key', 'foo');

        // When I call the scrobble method
        $time = time();
        Lastfm::shouldReceive('scrobble')
            ->once()
            ->with($song->artist->name, $song->title, $time, $song->album->name, 'foo');

        $song->scrobble($user, $time);

        // Then I see the song is scrobbled
    }

    /** @test */
    public function it_does_not_scrobble_if_the_user_is_not_connected_to_lastfm()
    {
        // Given there's a song
        /** @var Song $song */
        $song = factory(Song::class)->create();

        // And a user who is not connected to lastfm
        /** @var User $user */
        $user = factory(User::class)->create();
        $user->setPreference('lastfm_session_key', false);

        // When I call the scrobble method
        Lastfm::shouldNotReceive('scrobble');

        $song->scrobble($user, time());

        // The the song shouldn't be scrobbled
    }

    /** @test */
    public function it_gets_related_youtube_videos()
    {
        // Given there's a song
        /** @var Song $song */
        $song = factory(Song::class)->create();

        // When I get is related YouTube videos
        YouTube::shouldReceive('searchVideosRelatedToSong')
            ->once()
            ->with($song, 'foo')
            ->andReturn(['bar' => 'baz']);

        $videos = $song->getRelatedYouTubeVideos('foo');

        // Then I see the related YouTube videos returned
        $this->assertEquals(['bar' => 'baz'], $videos);
    }
}
