<?php

namespace App\Providers;

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
        'App\Events\SongLikeToggled' => [
            'App\Listeners\LoveTrackOnLastfm',
        ],

        'App\Events\SongStartedPlaying' => [
            'App\Listeners\UpdateLastfmNowPlaying',
        ],

        'App\Events\LibraryChanged' => [
            'App\Listeners\TidyLibrary',
            'App\Listeners\ClearMediaCache',
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
