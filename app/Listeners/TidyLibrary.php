<?php

namespace App\Listeners;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;

class TidyLibrary
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Fired every time a LibraryChanged event is triggered.
     * Remove empty albums and artists from our system.
     */
    public function handle()
    {
        $inUseAlbums = Song::select('album_id')->groupBy('album_id')->get()->lists('album_id');
        $inUseAlbums[] = Album::UNKNOWN_ID;
        Album::whereNotIn('id', $inUseAlbums)->delete();

        $inUseArtists = Album::select('artist_id')->groupBy('artist_id')->get()->lists('artist_id');
        $inUseArtists[] = Artist::UNKNOWN_ID;
        Artist::whereNotIn('id', $inUseArtists)->delete();
    }
}
