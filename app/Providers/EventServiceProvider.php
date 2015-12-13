<?php

namespace App\Providers;

use App\Facades\Media;
use App\Models\Song;
use App\Models\Album;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\SomeEvent' => [
            'App\Listeners\EventListener',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        // Generate a unique hash for a song from its path to be the ID
        Song::creating(function ($song) {
            $song->id = Media::getHash($song->path);
        });

        // Remove the cover file if the album is deleted
        Album::deleted(function ($album) {
            if ($album->hasCover) {
                @unlink(app()->publicPath().'/img/covers/'.$album->cover);
            }
        });
    }
}
