<?php

namespace App\Providers;

use App\Events\LibraryChanged;
use App\Events\MediaSyncCompleted;
use App\Events\PlaybackStarted;
use App\Events\SongLikeToggled;
use App\Events\SongsBatchLiked;
use App\Events\SongsBatchUnliked;
use App\Listeners\DeleteNonExistingRecordsPostSync;
use App\Listeners\LoveMultipleTracksOnLastfm;
use App\Listeners\LoveTrackOnLastfm;
use App\Listeners\PruneLibrary;
use App\Listeners\UnloveMultipleTracksOnLastfm;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Listeners\WriteSyncLog;
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

        PlaybackStarted::class => [
            UpdateLastfmNowPlaying::class,
        ],

        LibraryChanged::class => [
            PruneLibrary::class,
        ],

        MediaSyncCompleted::class => [
            DeleteNonExistingRecordsPostSync::class,
            WriteSyncLog::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        Album::observe(AlbumObserver::class);
    }
}
