<?php

namespace App\Listeners {
    if (function_exists(__NAMESPACE__.'/init_get')) {
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
    use App\Events\ArtistInformationFetched;
    use App\Models\Artist;
    use App\Services\MediaMetadataService;
    use Mockery\MockInterface;
    use Tests\TestCase;

    class DownloadArtistImageTest extends TestCase
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
            $artist = factory(Artist::class)->make(['image' => null]);
            $event = new ArtistInformationFetched($artist, ['image' => 'https://foo.bar/baz.jpg']);

            $this->mediaMetaDataService
                ->shouldReceive('downloadArtistImage')
                ->once()
                ->with($artist, 'https://foo.bar/baz.jpg');

            event($event);
        }
    }
}
