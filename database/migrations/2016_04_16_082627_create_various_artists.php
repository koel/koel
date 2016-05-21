<?php

use App\Models\Artist;
use Illuminate\Database\Migrations\Migration;

class CreateVariousArtists extends Migration
{
    /**
     * Create the "Various Artists".
     *
     * @return void
     */
    public function up()
    {
        Artist::unguard();

        $existingArtist = Artist::find(Artist::VARIOUS_ID);

        if ($existingArtist) {
            if ($existingArtist->name === Artist::VARIOUS_NAME) {
                goto ret;
            }

            // There's an existing artist with that special ID, but it's not our Various Artist
            // We move it to the end of the table.
            $latestArtist = Artist::orderBy('id', 'DESC')->first();
            $existingArtist->id = $latestArtist->id + 1;
            $existingArtist->save();
        }

        Artist::create([
            'id' => Artist::VARIOUS_ID,
            'name' => Artist::VARIOUS_NAME,
        ]);

        ret:
        Artist::reguard();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
