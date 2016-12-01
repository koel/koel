<?php

use App\Models\Artist;
use Illuminate\Database\Migrations\Migration;

class FixArtistAutoindexValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This is to fix the auto increment bug caused by 2016_04_16_082627_create_various_artists

        // Return if the database driver is not MySQL.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $latestArtist = Artist::orderBy('id', 'DESC')->first();
        DB::statement('ALTER TABLE artists AUTO_INCREMENT='.($latestArtist->id + 1));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
