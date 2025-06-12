<?php

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
    }
}
