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
use App\Services\LastfmService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Routing\Redirector;
use Mockery as m;
use Mockery\MockInterface;
use Tymon\JWTAuth\JWTAuth;

class LastfmTest extends TestCase
{
    use WithoutMiddleware;

    public function testGetSessionKey()
    {
        /** @var Client $client */
        $client = m::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../blobs/lastfm/session-key.xml')),
        ]);

        $this->assertEquals('foo', (new LastfmService($client))->getSessionKey('bar'));
    }

    public function testSetSessionKey()
    {
        $user = factory(User::class)->create();
        $this->postAsUser('api/lastfm/session-key', ['key' => 'foo'], $user);
        $user = User::find($user->id);
        $this->assertEquals('foo', $user->lastfm_session_key);
    }

    /** @test */
    public function user_can_connect_to_lastfm()
    {
        /** @var Redirector|MockInterface $redirector */
        $redirector = m::mock(Redirector::class);
        $redirector->shouldReceive('to')->once();

        /** @var Guard|MockInterface $guard */
        $guard = m::mock(Guard::class, ['user' => factory(User::class)->create()]);
        $auth = m::mock(JWTAuth::class, [
            'parseToken' => '',
            'getToken' => '',
        ]);

        (new LastfmController($guard))->connect($redirector, app(LastfmService::class), $auth);
    }

    public function testRetrieveAndStoreSessionKey()
    {
        /** @var LastfmCallbackRequest $request */
        $request = m::mock(LastfmCallbackRequest::class);
        $request->token = 'foo';
        /** @var LastfmService $lastfm */
        $lastfm = m::mock(LastfmService::class, ['getSessionKey' => 'bar']);

        $user = factory(User::class)->create();
        /** @var Guard $guard */
        $guard = m::mock(Guard::class, ['user' => $user]);

        (new LastfmController($guard))->callback($request, $lastfm);

        $this->assertEquals('bar', $user->lastfm_session_key);
    }

    public function testDisconnectUser()
    {
        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $this->deleteAsUser('api/lastfm/disconnect', [], $user);
        $user = User::find($user->id);
        $this->assertNull($user->lastfm_session_key);
    }

    /**
     * @throws Exception
     */
    public function testLoveTrack()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);

        $interaction = Interaction::create([
            'user_id' => $user->id,
            'song_id' => Song::first()->id,
        ]);

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = m::mock(LastfmService::class, ['enabled' => true]);
        $lastfm->shouldReceive('toggleLoveTrack')
            ->once()
            ->with($interaction->song->title, $interaction->song->album->artist->name, 'bar', false);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongLikeToggled($interaction, $user));
    }

    /**
     * @throws Exception
     */
    public function testUpdateNowPlayingStatus()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $song = Song::first();

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = m::mock(LastfmService::class, ['enabled' => true]);
        $lastfm->shouldReceive('updateNowPlaying')
            ->once()
            ->with($song->album->artist->name, $song->title, $song->album->name, $song->length, 'bar');

        (new UpdateLastfmNowPlaying($lastfm))->handle(new SongStartedPlaying($song, $user));
    }
}
