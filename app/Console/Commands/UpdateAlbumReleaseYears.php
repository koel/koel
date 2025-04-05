<?php

namespace App\Console\Commands;

use App\Models\Album;
use Illuminate\Console\Command;

class UpdateAlbumReleaseYears extends Command
{
    protected $signature = 'koel:update-album-release-years';
    protected $description = 'Update release years for all albums based on the first song associated with each one.';

    public function handle(): int
    {
        $this->info('Updating album release years...');

        $albums = Album::all();
        $count = 0;

        foreach ($albums as $album) {
            $firstSong = $album->songs()->first();

            if ($firstSong && $firstSong->year) {
                $album->year = $firstSong->year;
                $album->save();
                $count++;
            }
        }

        $this->info("Updated release years for $count albums.");

        return Command::SUCCESS;
    }
}
