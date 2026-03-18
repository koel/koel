<?php

namespace Tests\Unit\Repositories;

use App\Models\Song;
use App\Repositories\SongRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class SongRepositoryTest extends TestCase
{
    #[Test]
    public function findByHashReturnsMatchingSongForOwner(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['hash' => 'abc123', 'owner_id' => $user->id]);

        $result = app(SongRepository::class)->findByHash('abc123', $user);

        self::assertTrue($song->is($result));
    }

    #[Test]
    public function findByHashReturnsNullWhenNoSongWithHash(): void
    {
        $user = create_user();

        $result = app(SongRepository::class)->findByHash('deadbeef', $user);

        self::assertNull($result);
    }

    #[Test]
    public function findByHashScopesToOwnerOnly(): void
    {
        $userA = create_user();
        $userB = create_user();
        Song::factory()->createOne(['hash' => 'abc123', 'owner_id' => $userA->id]);

        $result = app(SongRepository::class)->findByHash('abc123', $userB);

        self::assertNull($result);
    }
}
