<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite doesn't support fulltext indexes on regular columns; nothing to drop or add.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Replace the per-column singles with a composite over the short-string display
        // fields. 'simple' language — none of the composite columns hold prose where English
        // stemming would earn its keep. The lyrics fulltext (added in the 2026-03 migration)
        // stays — it backs `SongRepository::searchByLyrics()` via the AI assistant's
        // PlaySongsByLyrics tool.
        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropFullText(['title']);
            $table->fullText(['title', 'artist_name', 'album_name'])->language('simple');
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->dropFullText(['name']);
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
