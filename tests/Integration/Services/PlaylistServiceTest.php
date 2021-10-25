<?php

namespace Tests\Integration\Services;

use App\Models\User;
use App\Services\PlaylistService;
use Tests\TestCase;

class PlaylistServiceTest extends TestCase
{
    private PlaylistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlaylistService::class);
    }

    public function testCreatePlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $playlist = $this->service->createPlaylist('foo', $user, []);
        self::assertFalse($playlist->getIsSmartAttribute());
    }

    public function testCreateSmartPlaylist(): void
    {
        $rules = [
            [
                'id' => 1634756491129,
                'model' => 'title',
                'operator' => 'is',
                'value' => ['foobar'],
            ],
        ];

        /** @var User $user */
        $user = User::factory()->create();

        $playlist = $this->service->createPlaylist('foo', $user, [], $rules);
        self::assertTrue($playlist->getIsSmartAttribute());
    }
}
