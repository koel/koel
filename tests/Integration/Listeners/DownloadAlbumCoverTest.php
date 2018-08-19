<?php

namespace App\Listeners {
    if (!function_exists(__NAMESPACE__ . '/init_get')) {
        function ini_get($key)
        {
            if ($key === 'allow_url_fopen') {
                return true;
            }

            return \ini_get($key);
        }
    }
}

namespace Tests\Integration\Listeners {
    use App\Events\AlbumInformationFetched;
    use App\Models\Album;
    use App\Services\MediaMetadataService;
    use Mockery\MockInterface;
    use Tests\TestCase;

    class DownloadAlbumCoverTest extends TestCase
    {
        /** @var MediaMetadataService|MockInterface */
        private $mediaMetaDataService;

        public function setUp()
        {
            parent::setUp();

            $this->mediaMetaDataService = $this->mockIocDependency(MediaMetadataService::class);
        }

        public function testHandle()
        {
            $album = factory(Album::class)->make(['cover' => null]);
            $event = new AlbumInformationFetched($album, ['image' => 'https://foo.bar/baz.jpg']);

            $this->mediaMetaDataService
                ->shouldReceive('downloadAlbumCover')
                ->once()
                ->with($album, 'https://foo.bar/baz.jpg');

            event($event);
        }
    }
}
