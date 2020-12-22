<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlaylistSongTable extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_song', static function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('playlist_id')->unsigned();
            $table->string('song_id', 32);
        });

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->foreign('playlist_id')->references('id')->on('playlists')->onDelete('cascade');
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::drop('playlist_song');
    }
}
