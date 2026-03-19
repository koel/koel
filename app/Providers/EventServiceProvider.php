<?php

namespace App\Providers;

use App\Events\LibraryChanged;
use App\Events\MediaScanCompleted;
use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\NewPlaylistCollaboratorJoined;
use App\Events\PlaybackStarted;
use App\Events\SongFavoriteToggled;
use App\Events\UserUnsubscribedFromPodcast;
use App\Listeners\DeleteNonExistingRecordsPostScan;
use App\Listeners\DeletePodcastIfNoSubscribers;
use App\Listeners\LoveMultipleTracksOnLastfm;
use App\Listeners\LoveTrackOnLastfm;
use App\Listeners\MakePlaylistSongsPublic;
use App\Listeners\PruneLibrary;
use App\Listeners\UnloveMultipleTracksOnLastfm;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Listeners\WriteScanLog;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseServiceProvider;

class EventServiceProvider extends BaseServiceProvider
{
    protected $listen = [
        SongFavoriteToggled::class => [
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
            DeleteNonExistingRecordsPostScan::class,
            PruneLibrary::class,
            WriteScanLog::class,
        ],

        NewPlaylistCollaboratorJoined::class => [
            MakePlaylistSongsPublic::class,
        ],

        UserUnsubscribedFromPodcast::class => [
            DeletePodcastIfNoSubscribers::class,
        ],
    ];
}
