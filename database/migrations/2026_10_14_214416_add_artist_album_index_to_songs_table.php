<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasIndex('songs', 'songs_artist_id_album_id_index')) {
            return;
        }

        Schema::table('songs', static function (Blueprint $table): void {
            $table->index(['artist_id', 'album_id']);
        });
    }
};
