<?php

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Database\Seeder;

class E2EDataSeeder extends Seeder
{
    private $sampleSongFullPath;

    public function run()
    {
        $this->sampleSongFullPath = realpath(__DIR__.'/../../tests/songs/full.mp3');

        factory(Artist::class, 20)->create()->each(function (Artist $artist) {
            factory(Album::class, 5)->create([
                'artist_id' => $artist->id,
            ])->each(function (Album $album) {
                factory(Song::class, 10)->create([
                   'album_id' => $album->id,
               ]);
            });
        });
    }
}
