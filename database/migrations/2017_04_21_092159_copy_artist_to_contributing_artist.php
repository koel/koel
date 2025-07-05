<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CopyArtistToContributingArtist extends Migration
{
    public function up(): void
    {
        DB::table('songs')
            ->join('albums', 'songs.album_id', '=', 'albums.id')
            ->join('artists', 'albums.artist_id', '=', 'artists.id')
            ->get(['songs.id', 'songs.contributing_artist_id', 'artists.id as artist_id'])
            ->each(static function ($song): void {
                if (!$song->contributing_artist_id) {
                    DB::table('songs')
                        ->where('id', $song->id)
                        ->update(['contributing_artist_id' => $song->artist_id]);
                }
            });
    }
}
