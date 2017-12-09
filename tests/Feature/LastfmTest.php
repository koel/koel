<?php

namespace Tests\Feature;

use App\Events\SongLikeToggled;
use App\Events\SongStartedPlaying;
use App\Http\Controllers\API\LastfmController;
use App\Http\Requests\API\LastfmCallbackRequest;
use App\Listeners\LoveTrackOnLastfm;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use App\Services\Lastfm;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Routing\Redirector;
use Mockery as m;
use Tymon\JWTAuth\JWTAuth;

class LastfmTest extends TestCase
{
    use WithoutMiddleware;

    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testGetSessionKey()
    {
        $client = m::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../blobs/lastfm/session-key.xml')),
        ]);

        $api = new Lastfm(null, null, $client);

        $this->assertEquals('foo', $api->getSessionKey('bar'));
    }

    /** @test */
    public function session_key_can_be_set()
    {
        $user = factory(User::class)->create();
        $this->postAsUser('api/lastfm/session-key', ['key' => 'foo'], $user);
        $user = User::find($user->id);
        $this->assertEquals('foo', $user->lastfm_session_key);
    }

    /** @test */
    public function user_can_connect_to_lastfm()
    {
        /** @var Redirector|m\MockInterface $redirector */
        $redirector = m::mock(Redirector::class);
        $redirector->shouldReceive('to')->once();

        /** @var Guard|m\MockInterface $guard */
        $guard = m::mock(Guard::class, ['user' => factory(User::class)->create()]);
        $auth = m::mock(JWTAuth::class, [
            'parseToken' => '',
            'getToken' => '',
        ]);

        (new LastfmController($guard))->connect($redirector, new Lastfm(), $auth);
    }

    /** @test */
    public function lastfm_session_key_can_be_retrieved_and_stored()
    {
        /** @var LastfmCallbackRequest|m\MockInterface $request */
        $request = m::mock(LastfmCallbackRequest::class);
        $request->token = 'foo';
        /** @var Lastfm|m\MockInterface $lastfm */
        $lastfm = m::mock(Lastfm::class, ['getSessionKey' => 'bar']);

        $user = factory(User::class)->create();
        /** @var Guard|m\MockInterface $guard */
        $guard = m::mock(Guard::class, ['user' => $user]);

        (new LastfmController($guard))->callback($request, $lastfm);

        $this->assertEquals('bar', $user->lastfm_session_key);
    }

    /** @test */
    public function user_can_disconnect_from_lastfm()
    {
        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $this->deleteAsUser('api/lastfm/disconnect', [], $user);
        $user = User::find($user->id);
        $this->assertNull($user->lastfm_session_key);
    }

    /** @test */
    public function user_can_love_a_track_on_lastfm()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);

        $interaction = Interaction::create([
            'user_id' => $user->id,
            'song_id' => Song::first()->id,
        ]);

        /** @var Lastfm|m\MockInterface $lastfm */
        $lastfm = m::mock(Lastfm::class, ['enabled' => true]);
        $lastfm->shouldReceive('toggleLoveTrack')
            ->with($interaction->song->title, $interaction->song->album->artist->name, 'bar', false);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction, $user));
    }

    /** @test */
    public function user_now_playing_status_can_be_updated_to_lastfm()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $song = Song::first();

        /** @var Lastfm|m\MockInterface $lastfm */
        $lastfm = m::mock(Lastfm::class, ['enabled' => true]);
        $lastfm->shouldReceive('updateNowPlaying')
            ->with($song->album->artist->name, $song->title, $song->album->name, $song->length, 'bar');

        (new UpdateLastfmNowPlaying($lastfm))->handle(new SongStartedPlaying($song, $user));
    }
}
