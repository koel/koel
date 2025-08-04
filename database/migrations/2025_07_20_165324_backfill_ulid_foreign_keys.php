<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Update albums.artist_ulid from artists.public_id
        DB::table('albums')->update([
            'artist_ulid' => DB::raw('(SELECT public_id FROM artists WHERE artists.id = albums.artist_id)'),
        ]);

        // Update songs.artist_ulid and songs.album_ulid
        DB::table('songs')->update([
            'artist_ulid' => DB::raw('(SELECT public_id FROM artists WHERE artists.id = songs.artist_id)'),
            'album_ulid'  => DB::raw('(SELECT public_id FROM albums WHERE albums.id = songs.album_id)'),
        ]);
    }
};
