<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\DownloadService;
use App\Values\Downloadable;
use Illuminate\Http\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class DownloadTest extends TestCase
{
    private MockInterface|DownloadService $downloadService;

    public function setUp(): void
    {
        parent::setUp();

        $this->downloadService = $this->mock(DownloadService::class);
    }

    #[Test]
    public function nonLoggedInUserCannotDownload(): void
    {
        $this->downloadService->shouldNotReceive('getDownloadable');

        $this->get('download/songs?songs[]=' . Song::factory()->createOne()->id)->assertUnauthorized();
    }

    #[Test]
    public function downloadOneSong(): void
    {
        $song = Song::factory()->createOne();
        $user = create_user();

        $this->downloadService
            ->expects('getDownloadable')
            ->with(Mockery::on(static fn (Song $retrievedSong): bool => $retrievedSong->is($song)))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get(
            "download/songs?songs[]={$song->id}&api_token=" . $user->createToken('Koel')->plainTextToken,
        )->assertOk();
    }

    #[Test]
    public function answerNotFoundWhenTheSongCannotBeServed(): void
    {
        $song = Song::factory()->createOne();

        $this->downloadService->expects('getDownloadable')->andReturnNull();

        $this->get(
            "download/songs?songs[]={$song->id}&api_token=" . create_user()->createToken('Koel')->plainTextToken,
        )->assertNotFound();
    }

    #[Test]
    public function refuseToDownloadSeveralSongsAtOnce(): void
    {
        $songs = Song::factory()->createMany(2);
        $user = create_user();

        $this->downloadService->shouldNotReceive('getDownloadable');

        $this->getJson(
            "download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}&api_token="
            . $user->createToken('Koel')->plainTextToken,
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
