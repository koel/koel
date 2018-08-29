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
use App\Observers\AlbumObserver;
use App\Observers\SongObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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

        Song::observe(SongObserver::class);
        Album::observe(AlbumObserver::class);
    }
}
