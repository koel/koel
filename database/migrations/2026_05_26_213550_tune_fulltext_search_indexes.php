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

        // Drop long-text-body indexes (lyrics) and per-column singles in favor of composites
        // over short-string display fields. 'simple' language across the board — none of the
        // indexed columns hold prose where English stemming would earn its keep.
        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropFullText(['title']);
            $table->dropFullText(['lyrics']);
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
