<?php

namespace App\Providers;

use App\Events\AlbumInformationFetched;
use App\Events\ArtistInformationFetched;
use App\Events\LibraryChanged;
use App\Events\SongLikeToggled;
use App\Events\SongStartedPlaying;
use App\Listeners\ClearMediaCache;
use App\Listeners\DownloadAlbumCover;
use App\Listeners\DownloadArtistImage;
use App\Listeners\LoveTrackOnLastfm;
use App\Listeners\TidyLibrary;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Album;
use App\Models\Song;
use App\Services\HelperService;
use Exception;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        SongLikeToggled::class => [
            LoveTrackOnLastfm::class,
        ],

        SongStartedPlaying::class => [
            UpdateLastfmNowPlaying::class,
        ],

        LibraryChanged::class => [
            TidyLibrary::class,
            ClearMediaCache::class,
        ],

        AlbumInformationFetched::class => [
            DownloadAlbumCover::class,
        ],

        ArtistInformationFetched::class => [
            DownloadArtistImage::class,
        ],
    ];

    /**
     * Register any other events for your application.
     */
    public function boot()
    {
        parent::boot();

        // Generate a unique hash for a song from its path to be the ID
        Song::creating(static function (Song $song): void {
            /** @var HelperService $helperService */
            $helperService = app(HelperService::class);
            $song->id = $helperService->getFileHash($song->path);
        });

        // Remove the cover file if the album is deleted
        Album::deleted(static function (Album $album): void {
            if ($album->has_cover) {
                try {
                    unlink($album->cover_path);
                } catch (Exception $e) {
                    Log::error($e);
                }
            }
        });
    }
}
