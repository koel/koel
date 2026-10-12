<?php

namespace Tests\Unit\Services\Integrations;

use App\Http\Integrations\CoverArtArchive\Requests\GetReleaseCoverRequest;
use App\Http\Integrations\CoverArtArchive\Requests\GetReleaseGroupCoverRequest;
use App\Http\Integrations\MusicBrainz\Requests\GetReleaseGroupForReleaseRequest;
use App\Models\Album;
use App\Services\Integrations\CoverArtArchiveService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\TestCase;

use function Tests\test_path;

class CoverArtArchiveServiceTest extends TestCase
{
    private CoverArtArchiveService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(CoverArtArchiveService::class);
    }

    #[Test]
    public function findsTheFrontCoverOfTheRelease(): void
    {
        Saloon::fake([
            GetReleaseCoverRequest::class => MockResponse::make(body: self::coverPayload()),
        ]);

        $album = Album::factory()->createOne(['mbid' => 'c771f7fc-9e62-4349-a2e3-ceaf7122bf5b']);

        self::assertSame(
            'https://coverartarchive.org/release/c771f7fc-9e62-4349-a2e3-ceaf7122bf5b/30501372565-1200.jpg',
            $this->service->tryGetAlbumCover($album),
        );
    }

    #[Test]
    public function fallsBackToTheReleaseGroupWhenTheReleaseHasNoCover(): void
    {
        Saloon::fake([
            GetReleaseCoverRequest::class => MockResponse::make(status: 404),
            GetReleaseGroupForReleaseRequest::class => MockResponse::make(body: [
                'release-group' => ['id' => '1b022e01-4da6-387b-8658-8678046e4cef'],
            ]),
            GetReleaseGroupCoverRequest::class => MockResponse::make(body: self::coverPayload()),
        ]);

        $album = Album::factory()->createOne(['mbid' => 'c771f7fc-9e62-4349-a2e3-ceaf7122bf5b']);

        self::assertSame(
            'https://coverartarchive.org/release/c771f7fc-9e62-4349-a2e3-ceaf7122bf5b/30501372565-1200.jpg',
            $this->service->tryGetAlbumCover($album),
        );

        Saloon::assertSent(GetReleaseGroupCoverRequest::class);
    }

    #[Test]
    public function findsNothingWhenNeitherTheReleaseNorItsGroupHasACover(): void
    {
        Saloon::fake([
            GetReleaseCoverRequest::class => MockResponse::make(status: 404),
            GetReleaseGroupForReleaseRequest::class => MockResponse::make(body: [
                'release-group' => ['id' => '1b022e01-4da6-387b-8658-8678046e4cef'],
            ]),
            GetReleaseGroupCoverRequest::class => MockResponse::make(status: 404),
        ]);

        $album = Album::factory()->createOne(['mbid' => 'c771f7fc-9e62-4349-a2e3-ceaf7122bf5b']);

        self::assertNull($this->service->tryGetAlbumCover($album));
    }

    #[Test]
    public function asksForNothingWhenTheAlbumHasNoIdentifier(): void
    {
        Saloon::fake([]);

        self::assertNull($this->service->tryGetAlbumCover(Album::factory()->createOne(['mbid' => null])));

        Saloon::assertNothingSent();
    }

    #[Test]
    public function asksForNothingWhenMusicBrainzIsDisabled(): void
    {
        config(['koel.services.musicbrainz.enabled' => false]);
        Saloon::fake([]);

        $album = Album::factory()->createOne(['mbid' => 'c771f7fc-9e62-4349-a2e3-ceaf7122bf5b']);

        self::assertNull($this->service->tryGetAlbumCover($album));

        Saloon::assertNothingSent();
    }

    private static function coverPayload(): array
    {
        return File::json(test_path('fixtures/cover-art-archive/front-cover.json'));
    }
}
