<?php

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;

class ArtistTableSeeder extends Seeder
{
    public function run(): void
    {
        Artist::firstOrCreate([
            'id' => Artist::UNKNOWN_ID,
        ], [
            'name' => Artist::UNKNOWN_NAME,
        ]);
    }
}
