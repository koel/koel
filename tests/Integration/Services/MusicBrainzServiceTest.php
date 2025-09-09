<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Pipelines\Encyclopedia\GetAlbumTracksUsingMbid;
use App\Pipelines\Encyclopedia\GetAlbumWikidataIdUsingReleaseGroupMbid;
use App\Pipelines\Encyclopedia\GetArtistWikidataIdUsingMbid;
use App\Pipelines\Encyclopedia\GetMbidForArtist;
use App\Pipelines\Encyclopedia\GetReleaseAndReleaseGroupMbidsForAlbum;
use App\Pipelines\Encyclopedia\GetWikipediaPageSummaryUsingPageTitle;
use App\Pipelines\Encyclopedia\GetWikipediaPageTitleUsingWikidataId;
use App\Services\MusicBrainzService;
use App\Values\Album\AlbumInformation;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

use function Tests\create_user;
use function Tests\test_path;

class MusicBrainzServiceTest extends TestCase
{
    private MusicBrainzService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(MusicBrainzService::class);
    }

    private function mockPipelinePipe(string $class, mixed $input, mixed $output): void
    {
        $expectation = $this->mock($class)
            ->expects('__invoke')
            ->with($input, Mockery::on(static fn ($next) => is_callable($next)));

        if ($output instanceof Throwable) {
            $expectation->andThrow($output);
        } else {
            $expectation->andReturnUsing(static fn ($_, $next) => $next($output));
        }
    }

    #[Test]
    public function getArtistInformation(): void
    {
        $this->mockPipelinePipe(GetMbidForArtist::class, 'Skid Row', 'sample-mbid');
        $this->mockPipelinePipe(GetArtistWikidataIdUsingMbid::class, 'sample-mbid', 'Q123456');
        $this->mockPipelinePipe(GetWikipediaPageTitleUsingWikidataId::class, 'Q123456', 'Skid Row (American band)');

        $this->mockPipelinePipe(
            GetWikipediaPageSummaryUsingPageTitle::class,
            'Skid Row (American band)',
            File::json(test_path('fixtures/wikipedia/artist-page-summary.json'))
        );

        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Skid Row']);

        $info = $this->service->getArtistInformation($artist);

        self::assertSame([
            'url' => 'https://en.wikipedia.org/wiki/Skid_Row_(American_band)',
            'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/2023_Sweden_Rock_-_3330_%2853049443466%29.jpg/330px-2023_Sweden_Rock_-_3330_%2853049443466%29.jpg', // @phpcs-ignore
            'bio' => [
                'summary' => 'Skid Row is an American rock band formed in 1986…',
                'full' => '<p><b>Skid Row</b> is an American rock band formed in 1986…</p>',
            ],
        ], $info->toArray());
    }

    #[Test]
    public function getArtistInformationReturnsNullUponAnyErrorInThePipeline(): void
    {
        $this->mockPipelinePipe(GetMbidForArtist::class, 'Skid Row', 'sample-mbid');

        $this->mockPipelinePipe(
            GetArtistWikidataIdUsingMbid::class,
            'sample-mbid',
            new Exception('Something went wrong'),
        );

        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Skid Row']);

        self::assertNull($this->service->getArtistInformation($artist));
    }

    #[Test]
    public function getAlbumInformation(): void
    {
        $recordingsJson = File::json(test_path('fixtures/musicbrainz/recordings.json'));

        $tracks = [];

        foreach (Arr::get($recordingsJson, 'media', []) as $media) {
            array_push($tracks, ...Arr::get($media, 'tracks', []));
        }

        $this->mockPipelinePipe(
            GetReleaseAndReleaseGroupMbidsForAlbum::class,
            [
                'album' => 'Slave to the Grind',
                'artist' => 'Skid Row',
            ],
            ['sample-album-mbid', 'sample-release-group-mbid'],
        );

        $this->mockPipelinePipe(GetAlbumTracksUsingMbid::class, 'sample-album-mbid', $tracks);
        $this->mockPipelinePipe(GetAlbumWikidataIdUsingReleaseGroupMbid::class, 'sample-release-group-mbid', 'Q123456');
        $this->mockPipelinePipe(GetWikipediaPageTitleUsingWikidataId::class, 'Q123456', 'Slave to the Grind');

        $this->mockPipelinePipe(
            GetWikipediaPageSummaryUsingPageTitle::class,
            'Slave to the Grind',
            File::json(test_path('fixtures/wikipedia/album-page-summary.json'))
        );

        $user = create_user();

        /** @var Album $album */
        $album = Artist::factory() // @phpstan-ignore-line
            ->for($user)
            ->create(['name' => 'Skid Row'])
            ->albums()
            ->create([
                'name' => 'Slave to the Grind',
                'user_id' => $user->id,
            ]);

        self::assertInstanceOf(AlbumInformation::class, $this->service->getAlbumInformation($album));
        // eh, good enough
    }

    #[Test]
    public function getAlbumInformationReturnsNullUponAnyErrorInThePipeline(): void
    {
        $this->mockPipelinePipe(
            GetReleaseAndReleaseGroupMbidsForAlbum::class,
            [
                'album' => 'Slave to the Grind',
                'artist' => 'Skid Row',
            ],
            ['sample-album-mbid', 'sample-release-group-mbid'],
        );

        $this->mockPipelinePipe(GetAlbumTracksUsingMbid::class, 'sample-album-mbid', new Exception('Oopsie'));

        $user = create_user();

        /** @var Album $album */
        $album = Artist::factory() // @phpstan-ignore-line
            ->for($user)
            ->create(['name' => 'Skid Row'])
            ->albums()
            ->create([
                'name' => 'Slave to the Grind',
                'user_id' => $user->id,
            ]);

        self::assertNull($this->service->getAlbumInformation($album));
    }
}
