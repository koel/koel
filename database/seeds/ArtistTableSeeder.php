<?php

use App\Models\Artist;
use Illuminate\Database\Seeder;

class ArtistTableSeeder extends Seeder
{
    public function run()
    {
        Artist::firstOrCreate([
            'id' => Artist::UNKNOWN_ID,
        ], [
            'id' => Artist::UNKNOWN_ID,
            'name' => Artist::UNKNOWN_NAME,
        ]);
    }
}
