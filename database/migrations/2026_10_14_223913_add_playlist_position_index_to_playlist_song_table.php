<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasIndex('playlist_song', 'playlist_song_playlist_id_position_index')) {
            return;
        }

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->index(['playlist_id', 'position']);
        });
    }
};
