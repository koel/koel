<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddContributingArtistIdIntoSongs extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->integer('contributing_artist_id')->unsigned()->nullable()->after('album_id');
            $table->foreign('contributing_artist_id')->references('id')->on('artists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropColumn('contributing_artist_id');
        });
    }
}
