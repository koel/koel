<?php

namespace Tests\Integration\Services;

use App\Models\Song;
use App\Services\DownloadService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DownloadServiceTest extends TestCase
{
    private DownloadService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(DownloadService::class);
    }

    #[Test]
    public function serveEpisodeFromLocalCopy(): void
    {
        Http::fake([
            'https://example.com/episode.mp3' => Http::response('foo'),
        ]);

        $episode = Song::factory()
            ->asEpisode()
            ->createOne([
                'path' => 'https://example.com/episode.mp3',
            ]);

        $path = $this->service->getLocalPathOrDownloadableUrl($episode);

        self::assertSame('foo', File::get($path));
    }
}
