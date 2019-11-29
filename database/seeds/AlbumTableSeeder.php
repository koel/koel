<?php

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\Seeder;

class AlbumTableSeeder extends Seeder
{
    public function run()
    {
        Album::firstOrCreate([
            'id' => Album::UNKNOWN_ID
        ], [
            'id' => Album::UNKNOWN_ID,
            'artist_id' => Artist::UNKNOWN_ID,
            'name' => Album::UNKNOWN_NAME,
            'cover' => Album::UNKNOWN_COVER,
        ]);
    }
}
