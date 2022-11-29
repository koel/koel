<?php

namespace Tests\Integration\Services;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Services\PlaylistService;
use App\Services\SmartPlaylistService;
use App\Values\SmartPlaylistRule;
use Tests\Feature\TestCase as FeatureBaseTestCase;

class SmartPlaylistServiceTest extends FeatureBaseTestCase
{
    public function testGetSongsForPlaylistAndUser()
    {
        $rule = SmartPlaylistRule::create([
            'model' => 'artist.name',
            'operator' => SmartPlaylistRule::OPERATOR_IS,
            'value' => ['Bob Dylan'],
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $this->postAs('api/playlist', [
            'name' => 'Smart Foo Bar',
            'rules' => [
                [
                    'id' => 12345,
                    'rules' => [$rule->toArray()],
                ],
            ]
        ], $user);

        /** @var Playlist $playlist */
        $playlist = Playlist::query()->orderByDesc('id')->first();

        /** @var PlaylistService $playListService */
        $playListService = app(PlaylistService::class);
        $playListService->addSongsToPlaylist($playlist, Song::factory(3)->create([
            'artist_id' => $user->id
        ])->pluck('id')->toArray());

        $service = app(SmartPlaylistService::class);
        $this->assertEquals(
            $service->getSongs($playlist, $user)->pluck('id')->toArray(),
            $playlist->songs->pluck('id')->toArray()
        );

//        $this->get("/api/playlist/{$playlist}/songs")->dd();
    }
}
