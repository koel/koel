<?php

use App\Models\Song;
use Illuminate\Database\Migrations\Migration;

class CopyArtistToContributingArtist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Song::with('album', 'album.artist')->get()->each(function (Song $song) {
            if (!$song->contributing_artist_id) {
                $song->contributing_artist_id = $song->album->artist->id;
                $song->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
