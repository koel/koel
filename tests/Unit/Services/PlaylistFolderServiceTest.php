<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\PlaylistFolderService;
use Tests\TestCase;

class PlaylistFolderServiceTest extends TestCase
{
    private PlaylistFolderService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new PlaylistFolderService();
    }

    public function testCreate(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        self::assertCount(0, $user->playlist_folders);

        $this->service->createFolder($user, 'Classical');

        self::assertCount(1, $user->refresh()->playlist_folders);
        self::assertSame('Classical', $user->playlist_folders[0]->name);
    }
}
