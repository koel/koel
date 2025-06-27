<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Helpers\Uuid;
use App\Models\Song;
use App\Services\SmartPlaylistService;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class SmartPlaylistServiceTest extends PlusTestCase
{
    private SmartPlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SmartPlaylistService::class);
    }

    #[Test]
    public function ownSongsOnlyOption(): void
    {
        $owner = create_user();
        $matches = Song::factory()->count(2)->for($owner, 'owner')->create(['title' => 'Foo Something']);

        Song::factory()->count(1)->create(['title' => 'Foo Something']);
        Song::factory()->count(2)->create(['title' => 'Bar Something']);

        $playlist = create_playlist(
            attributes: [
                'rules' =>  [
                    [
                        'id' => Uuid::generate(),
                        'rules' => [
                            [
                                'id' => Uuid::generate(),
                                'model' => 'title',
                                'operator' => 'is',
                                'value' => ['Foo Something'],
                            ],
                        ],
                    ],
                ],
                'own_songs_only' => true,
            ],
            owner: $owner,
        );

        self::assertEqualsCanonicalizing(
            $matches->modelKeys(),
            $this->service->getSongs($playlist, $owner)->modelKeys()
        );
    }
}
