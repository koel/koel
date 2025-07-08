<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('artist_name')->nullable()->index();
            $table->string('album_name')->nullable()->index();
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->string('artist_name')->nullable()->index();
        });

        $pdo = DB::connection()->getPdo();

        DB::table('songs')
            ->join('albums', 'songs.album_id', '=', 'albums.id')
            ->join('artists', 'albums.artist_id', '=', 'artists.id')
            ->whereNotNull('songs.artist_id')
            ->whereNotNull('songs.album_id')
            ->orderBy('songs.created_at')
            ->select('songs.id', 'artists.name as artist_name', 'albums.name as album_name')
            ->chunk(100, static function ($songs) use ($pdo): void {
                $artistCases = [];
                $albumCases = [];
                $songIds = [];

                foreach ($songs as $song) {
                    $songIds[] = $song->id;
                    $artistCases[$song->id] = $song->artist_name;
                    $albumCases[$song->id] = $song->album_name;
                }

                // Build CASE statements for artist_id
                $artistCaseSql = 'CASE id ';

                foreach ($artistCases as $songId => $artistName) {
                    $artistCaseSql .= sprintf("WHEN '{$songId}' THEN %s ", $pdo->quote($artistName));
                }

                $artistCaseSql .= 'ELSE artist_name END';

                // Build CASE statements for album_id
                $albumCaseSql = 'CASE id ';

                foreach ($albumCases as $songId => $albumName) {
                    $albumCaseSql .= sprintf("WHEN '{$songId}' THEN %s ", $pdo->quote($albumName));
                }

                $albumCaseSql .= 'ELSE album_name END';

                // Run single batch update query
                DB::table('songs')
                    ->whereIn('id', $songIds)
                    ->update([
                        'artist_name' => DB::raw($artistCaseSql),
                        'album_name' => DB::raw($albumCaseSql),
                    ]);
            });

        // Update albums with artist names
        DB::table('albums')
            ->join('artists', 'albums.artist_id', '=', 'artists.id')
            ->whereNotNull('albums.artist_id')
            ->orderBy('albums.created_at')
            ->select('albums.id', 'artists.name as artist_name')
            ->chunk(100, static function ($albums) use ($pdo): void {
                $albumCases = [];
                $albumIds = [];

                foreach ($albums as $album) {
                    $albumIds[] = $album->id;
                    $albumCases[$album->id] = $album->artist_name;
                }

                // Build CASE statements for artist_name
                $albumCaseSql = 'CASE id ';

                foreach ($albumCases as $albumId => $artistName) {
                    $albumCaseSql .= sprintf("WHEN '{$albumId}' THEN %s ", $pdo->quote($artistName));
                }

                $albumCaseSql .= 'ELSE artist_name END';

                // Run single batch update query
                DB::table('albums')
                    ->whereIn('id', $albumIds)
                    ->update([
                        'artist_name' => DB::raw($albumCaseSql),
                    ]);
            });
    }
};
