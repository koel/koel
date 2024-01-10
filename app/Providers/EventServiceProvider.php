<?php

namespace App\Providers;

use App\Events\LibraryChanged;
use App\Events\MediaScanCompleted;
use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\PlaybackStarted;
use App\Events\SongLikeToggled;
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

        MultipleSongsLiked::class => [
            LoveMultipleTracksOnLastfm::class,
        ],

        MultipleSongsUnliked::class => [
            UnloveMultipleTracksOnLastfm::class,
        ],

        PlaybackStarted::class => [
            UpdateLastfmNowPlaying::class,
        ],

        LibraryChanged::class => [
            PruneLibrary::class,
        ],

        MediaScanCompleted::class => [
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
