<?php

use App\Models\Song;
use Illuminate\Database\Migrations\Migration;

class CopyArtistToContributingArtist extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Song::with('album', 'album.artist')->get()->each(static function (Song $song): void {
            if (!$song->contributing_artist_id) {
                $song->contributing_artist_id = $song->album->artist->id;
                $song->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
    }
}
