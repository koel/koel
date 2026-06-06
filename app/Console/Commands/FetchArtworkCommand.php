<?php

namespace App\Console\Commands;

use App\Builders\AlbumBuilder;
use App\Builders\ArtistBuilder;
use App\Models\Album;
use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;
use App\Services\Integrations\MusicBrainzService;
use App\Services\Integrations\SpotifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FetchArtworkCommand extends Command
{
    protected $signature = 'koel:fetch-artwork {--delay=1 : Time in seconds between requests to avoid rate limits}';
    protected $description = 'Attempt to fetch artist and album artworks from available sources.';

    public function __construct(
        private readonly EncyclopediaService $encyclopedia,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $delay = (int) $this->option('delay');

        if (!SpotifyService::enabled() && !MusicBrainzService::enabled()) {
            $this->components->error('Please configure Spotify and/or MusicBrainz integration first.');

            return self::FAILURE;
        }

        $this->components->info('Fetching artist images...');

        Artist::query()
            ->whereNotIn('name', [Artist::UNKNOWN_NAME, Artist::VARIOUS_NAME])
            ->where(static fn (ArtistBuilder $query) => $query->whereNull('image')->orWhere('image', ''))
            ->orderBy('name')
            ->lazy()
            ->each(function (Artist $artist) use ($delay): void {
                Cache::forget(cache_key('artist information', $artist->name));

                $this->encyclopedia->getArtistInformation($artist);

                $status = $artist->image ? '<info>OK</info>' : '<error>Failed</error>';
                $this->components->twoColumnDetail($artist->name, $status);

                sleep($delay);
            });

        $this->components->info('Fetching album covers...');

        Album::query()
            ->whereNot('name', Album::UNKNOWN_NAME)
            ->whereNotIn('artist_name', [Artist::UNKNOWN_NAME, Artist::VARIOUS_NAME])
            ->where(static fn (AlbumBuilder $query) => $query->whereNull('cover')->orWhere('cover', ''))
            ->orderBy('name')
            ->lazy()
            ->each(function (Album $album) use ($delay): void {
                Cache::forget(cache_key('album information', $album->name));

                $this->encyclopedia->getAlbumInformation($album);

                $status = $album->cover ? '<info>OK</info>' : '<error>Failed</error>';
                $this->components->twoColumnDetail($album->name . ' - ' . $album->artist_name, $status);

                sleep($delay);
            });

        $this->components->success('All done!');

        return self::SUCCESS;
    }
}
