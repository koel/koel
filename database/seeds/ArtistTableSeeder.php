<?php

use Illuminate\Database\Seeder;
use App\Models\Artist;

class ArtistTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Artist::create([
            'id' => Artist::UNKNOWN_ID,
            'name' => Artist::UNKNOWN_NAME,
        ]);
    }
}
