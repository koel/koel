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
            if (Schema::hasIndex('songs', 'songs_title_artist_name_album_name_fulltext')) {
                $table->dropFullText(['title', 'artist_name', 'album_name']);
            }

            if (!Schema::hasIndex('songs', 'songs_title_fulltext')) {
                $table->fullText('title');
            }
        });

        Schema::table('albums', static function (Blueprint $table): void {
            if (Schema::hasIndex('albums', 'albums_name_artist_name_fulltext')) {
                $table->dropFullText(['name', 'artist_name']);
            }

            if (!Schema::hasIndex('albums', 'albums_name_fulltext')) {
                $table->fullText('name');
            }
        });

        Schema::table('artists', static function (Blueprint $table): void {
            if (Schema::hasIndex('artists', 'artists_name_fulltext')) {
                $table->dropFullText(['name']);
            }
        });

        Schema::table('genres', static function (Blueprint $table): void {
            if (Schema::hasIndex('genres', 'genres_name_fulltext')) {
                $table->dropFullText(['name']);
            }
        });

        Schema::table('playlists', static function (Blueprint $table): void {
            if (Schema::hasIndex('playlists', 'playlists_name_description_fulltext')) {
                $table->dropFullText(['name', 'description']);
            }
        });

        Schema::table('radio_stations', static function (Blueprint $table): void {
            if (Schema::hasIndex('radio_stations', 'radio_stations_name_description_fulltext')) {
                $table->dropFullText(['name', 'description']);
            }
        });

        Schema::table('podcasts', static function (Blueprint $table): void {
            if (Schema::hasIndex('podcasts', 'podcasts_title_description_author_fulltext')) {
                $table->dropFullText(['title', 'description', 'author']);
            }
        });
    }
};
