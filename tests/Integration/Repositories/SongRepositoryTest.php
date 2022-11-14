<?php

namespace Tests\Integration\Repositories;

use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Tests\TestCase;

class SongRepositoryTest extends TestCase
{
    private SongRepository $songRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = app(SongRepository::class);
    }

    public function testGetOneByPath(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => 'foo']);
        self::assertSame($song->id, $this->songRepository->getOneByPath('foo')->id);
    }

    public function testGetAllHostedOnS3()
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => 's3://song/test']);

        self::assertSame($song->s3_params,
            $this->songRepository->getAllHostedOnS3()->first()->s3_params);
    }

    public function testGetRecentlyAdded()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var Album $album */
        $album = Album::factory([
            'artist_id' => $user->id,
        ])->create();

        /** @var Song $songs */
        $songs = Song::factory(10, [
            'album_id' => $album->id,
            'artist_id' => $album->artist->id
        ])->create();

        $this->assertEquals(10, $this->songRepository->getRecentlyAdded(10)->count());
        $this->assertEquals(10, $this->songRepository->getRecentlyAdded(10, $user)->count());
        $this->assertDatabaseCount(Song::class , 10);
    }
}
