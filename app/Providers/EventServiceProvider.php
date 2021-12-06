<?php

namespace App\Providers;

use App\Events\AlbumInformationFetched;
use App\Events\ArtistInformationFetched;
use App\Events\LibraryChanged;
use App\Events\MediaCacheObsolete;
use App\Events\MediaSyncCompleted;
use App\Events\SongLikeToggled;
use App\Events\SongsBatchLiked;
use App\Events\SongsBatchUnliked;
use App\Events\SongStartedPlaying;
use App\Listeners\ClearMediaCache;
use App\Listeners\DeleteNonExistingRecordsPostSync;
use App\Listeners\DownloadAlbumCover;
use App\Listeners\DownloadArtistImage;
use App\Listeners\LoveMultipleTracksOnLastfm;
use App\Listeners\LoveTrackOnLastfm;
use App\Listeners\TidyLibrary;
use App\Listeners\UnloveMultipleTracksOnLastfm;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Album;
use App\Models\Song;
use App\Observers\AlbumObserver;
use App\Observers\SongObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SongLikeToggled::class => [
            LoveTrackOnLastfm::class,
        ],

        SongsBatchLiked::class => [
            LoveMultipleTracksOnLastfm::class,
        ],

        SongsBatchUnliked::class => [
            UnloveMultipleTracksOnLastfm::class,
        ],

        SongStartedPlaying::class => [
            UpdateLastfmNowPlaying::class,
        ],

        LibraryChanged::class => [
            TidyLibrary::class,
            ClearMediaCache::class,
        ],

        MediaCacheObsolete::class => [
            ClearMediaCache::class,
        ],

        AlbumInformationFetched::class => [
            DownloadAlbumCover::class,
        ],

        ArtistInformationFetched::class => [
            DownloadArtistImage::class,
        ],

        MediaSyncCompleted::class => [
            DeleteNonExistingRecordsPostSync::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        Song::observe(SongObserver::class);
        Album::observe(AlbumObserver::class);
    }
}
