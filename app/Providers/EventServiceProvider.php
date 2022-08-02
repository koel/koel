<?php

namespace App\Providers;

use App\Events\LibraryChanged;
use App\Events\MediaSyncCompleted;
use App\Events\SongLikeToggled;
use App\Events\SongsBatchLiked;
use App\Events\SongsBatchUnliked;
use App\Events\SongStartedPlaying;
use App\Listeners\ClearMediaCache;
use App\Listeners\DeleteNonExistingRecordsPostSync;
use App\Listeners\LoveMultipleTracksOnLastfm;
use App\Listeners\LoveTrackOnLastfm;
use App\Listeners\PruneLibrary;
use App\Listeners\UnloveMultipleTracksOnLastfm;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Models\Album;
use App\Observers\AlbumObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseServiceProvider;

class EventServiceProvider extends BaseServiceProvider
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
            PruneLibrary::class,
            ClearMediaCache::class,
        ],

        MediaSyncCompleted::class => [
            DeleteNonExistingRecordsPostSync::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        Album::observe(AlbumObserver::class);
    }
}
