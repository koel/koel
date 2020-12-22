<?php

use App\Models\Artist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixArtistAutoindexValue extends Migration
{
    public function up(): void
    {
        // This is to fix the auto increment bug caused by 2016_04_16_082627_create_various_artists

        // Return if the database driver is not MySQL.
        if (DB::getDriverName() !== 'mysql') { // @phpstan-ignore-line
            return;
        }

        /** @var Artist $latestArtist */
        $latestArtist = Artist::orderBy('id', 'DESC')->first();
        DB::statement('ALTER TABLE artists AUTO_INCREMENT=' . ($latestArtist->id + 1));
    }

    public function down(): void
    {
    }
}
