<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Models\Playlist;
use App\Models\Song;
use App\Services\SmartPlaylistService;
use Illuminate\Support\Str;
use Tests\PlusTestCase;

use function Tests\create_user;

class SmartPlaylistServiceTest extends PlusTestCase
{
    private SmartPlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SmartPlaylistService::class);
    }

    public function testOwnSongsOnlyOption(): void
    {
        $owner = create_user();
        $matches = Song::factory()->count(3)->for($owner, 'owner')->create(['title' => 'Foo Something']);

        Song::factory()->count(2)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()
            ->for($owner)
            ->create([
                'rules' =>  [
                    [
                        'id' => Str::uuid()->toString(),
                        'rules' => [
                            [
                                'id' => Str::uuid()->toString(),
                                'model' => 'title',
                                'operator' => 'is',
                                'value' => ['Foo Something'],
                            ],
                        ],
                    ],
                ],
                'own_songs_only' => true,
            ]);

        self::assertEqualsCanonicalizing(
            $matches->pluck('id')->all(),
            $this->service->getSongs($playlist, $owner)->pluck('id')->all()
        );
    }
}
