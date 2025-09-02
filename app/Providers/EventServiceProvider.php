<?php

namespace App\Providers;

use App\Events\LibraryChanged;
use App\Events\MediaScanCompleted;
use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\NewPlaylistCollaboratorJoined;
use App\Events\PlaybackStarted;
use App\Events\SongFavoriteToggled;
use App\Listeners\DeleteNonExistingRecordsPostScan;
use App\Listeners\LoveMultipleTracksOnLastfm;
use App\Listeners\LoveTrackOnLastfm;
use App\Listeners\MakePlaylistSongsPublic;
use App\Listeners\PruneLibrary;
use App\Listeners\UnloveMultipleTracksOnLastfm;
use App\Listeners\UpdateLastfmNowPlaying;
use App\Listeners\WriteScanLog;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Folder;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use App\Models\RadioStation;
use App\Models\User;
use App\Observers\AlbumObserver;
use App\Observers\ArtistObserver;
use App\Observers\FolderObserver;
use App\Observers\GenreObserver;
use App\Observers\PlaylistCollaborationTokenObserver;
use App\Observers\PlaylistObserver;
use App\Observers\RadioStationObserver;
use App\Observers\UserObserver;
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
    ];

    public function boot(): void
    {
        parent::boot();

        Album::observe(AlbumObserver::class);
        Artist::observe(ArtistObserver::class);
        Folder::observe(FolderObserver::class);
        Playlist::observe(PlaylistObserver::class);
        PlaylistCollaborationToken::observe(PlaylistCollaborationTokenObserver::class);
        Genre::observe(GenreObserver::class);
        User::observe(UserObserver::class);
        RadioStation::observe(RadioStationObserver::class);
    }
}
