<?php

use App\Models\Artist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVariousArtists extends Migration
{
    /**
     * Create the "Various Artists".
     *
     */
    public function up(): void
    {
        // Make sure modified artists cascade the album's artist_id field.
        Schema::table('albums', static function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') { // @phpstan-ignore-line
                $table->dropForeign('albums_artist_id_foreign');
            }

            $table->foreign('artist_id')->references('id')->on('artists')->onUpdate('cascade')->onDelete('cascade');
        });

        Artist::unguard();

        /** @var Artist|null $existingArtist */
        $existingArtist = Artist::query()->find(Artist::VARIOUS_ID);

        if ($existingArtist) {
            if ($existingArtist->name === Artist::VARIOUS_NAME) {
                goto ret;
            }

            // There's an existing artist with that special ID, but it's not our Various Artist
            // We move it to the end of the table.
            /** @var Artist $latestArtist */
            $latestArtist = Artist::query()->orderByDesc('id')->first();
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
     */
    public function down(): void
    {
    }
}
