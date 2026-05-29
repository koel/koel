<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('songs', static function (Blueprint $table): void {
            if (Schema::hasIndex('songs', 'songs_title_fulltext')) {
                $table->dropFullText(['title']);
            }
            $table->fullText(['title', 'artist_name', 'album_name'])->language('simple');
        });

        Schema::table('albums', static function (Blueprint $table): void {
            if (Schema::hasIndex('albums', 'albums_name_fulltext')) {
                $table->dropFullText(['name']);
            }
            $table->fullText(['name', 'artist_name'])->language('simple');
        });

        Schema::table('artists', static function (Blueprint $table): void {
            $table->fullText('name')->language('simple');
        });

        Schema::table('genres', static function (Blueprint $table): void {
            $table->fullText('name')->language('simple');
        });

        Schema::table('playlists', static function (Blueprint $table): void {
            $table->fullText(['name', 'description'])->language('simple');
        });

        Schema::table('radio_stations', static function (Blueprint $table): void {
            $table->fullText(['name', 'description'])->language('simple');
        });

        Schema::table('podcasts', static function (Blueprint $table): void {
            $table->fullText(['title', 'description', 'author'])->language('simple');
        });
    }
};
