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
use App\Models\File;
use App\Models\Song;
use Exception;
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

        // Generate a unique hash for a song from its path to be the ID
        Song::creating(function ($song) {
            $song->id = File::getHash($song->path);
        });

        // Remove the cover file if the album is deleted
        Album::deleted(function ($album) {
            if ($album->hasCover) {
                try {
                    unlink(app()->publicPath()."/public/img/covers/{$album->cover}");
                } catch (Exception $e) {
                }
            }
        });
    }
}
