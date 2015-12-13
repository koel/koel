<?php

use Illuminate\Database\Seeder;
use App\Models\Album;
use App\Models\Artist;

class AlbumTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Album::create([
            'id' => Album::UNKNOWN_ID,
            'artist_id' => Artist::UNKNOWN_ID,
            'name' => Album::UNKNOWN_NAME,
            'cover' => Album::UNKNOWN_COVER,
        ]);
    }
}
