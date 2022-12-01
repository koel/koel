<?php

namespace Tests\Integration\Services;

use App\Exceptions\NonSmartPlaylistException;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Services\SmartPlaylistService;
use App\Values\SmartPlaylistRuleGroupCollection;
use Tests\TestCase;

class SmartPlaylistServiceTest extends TestCase
{
    private SmartPlaylistService $smartPlaylistService;

    public function setUp(): void
    {
        parent::setUp();

        $this->smartPlaylistService = app(SmartPlaylistService::class);
    }

    public function testGetSongsBasedOnMySmartPlaylistViaSmartPlaylistService()
    {
        $rules = SmartPlaylistRuleGroupCollection::create([
            [
                'id' => 1634756491129,
                'rules' => [
                    [
                        'id' => 1634756491129,
                        'model' => 'title',
                        'operator' => 'is',
                        'value' => ['smart'],
                    ],
                    [
                        "id" => 1669824399012,
                        "model" => "year",
                        "operator" => "isGreaterThan",
                        "value" => [2000]
                    ]
                ],
            ],
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory([
            'user_id' => $user->id,
            'rules' => $rules
        ])->create()->first();

        /** @var Song $songs */
        $newSongs = Song::factory(3, [
            'year' => random_int(2001, 2010),
            'title' => 'smart'
        ])->create();

        $playlist->songs()->sync($newSongs->pluck('id'));

        $songs = $this->smartPlaylistService->getSongs($playlist);
        self::assertInstanceOf(Song::class, $songs->first());
        self::assertEquals($songs->count(), $newSongs->count());
        self::assertEquals($songs->pluck('lyrics'), $newSongs->pluck('lyrics'));
    }

    public function testIfPlaylistIsNotSmartThenThrowsNonSmartPlaylistException()
    {
        self::expectException(NonSmartPlaylistException::class);
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create()->first();

        /** @var Song $songs */
        $newSongs = Song::factory(3, [
            'title' => 'foo'
        ])->create();

        $playlist->songs()->sync($newSongs->pluck('id'));

        $this->smartPlaylistService->getSongs($playlist);
    }
}
