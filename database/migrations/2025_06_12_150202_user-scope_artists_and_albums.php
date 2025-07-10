<?php

use App\Facades\License;
use App\Helpers\Ulid;
use App\Models\Song;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->string('public_id', 26)->unique()->nullable();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->string('public_id', 26)->unique()->nullable();
            //Strictly saying we don't need user_id, but it's better for simplicity and performance.
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        // set the default user_id for existing albums and artists to the first admin
        $firstAdmin = DB::table('users')->where('is_admin', true)->oldest()->first();

        if (!$firstAdmin) {
            // No first admin exists, i.e., the database is empty (during the initial setup).
            return;
        }

        DB::table('artists')
            ->chunkById(200, static function ($artists) use ($firstAdmin): void {
                $cases = '';
                $ids = [];

                foreach ($artists as $artist) {
                    $ulid = Ulid::generate();
                    $cases .= "WHEN $artist->id THEN '$ulid' ";
                    $ids[] = $artist->id;
                }

                DB::table('artists')
                    ->whereIn('id', $ids)
                    ->update([
                        'user_id' => $firstAdmin->id,
                        'public_id' => DB::raw("CASE id $cases END"),
                    ]);
            });

        DB::table('albums')
            ->chunkById(200, static function ($albums) use ($firstAdmin): void {
                $cases = '';
                $ids = [];

                foreach ($albums as $album) {
                    $ulid = Ulid::generate();
                    $cases .= "WHEN $album->id THEN '$ulid' ";
                    $ids[] = $album->id;
                }

                DB::table('albums')
                    ->whereIn('id', $ids)
                    ->update([
                        'user_id' => $firstAdmin->id,
                        'public_id' => DB::raw("CASE id $cases END"),
                    ]);
            });

        // For the CE, we stop here. All artists and albums are owned by the default user and shared across all users.
        if (License::isCommunity()) {
            return;
        }

        // For Koel Plus, we need to update songs that are not owned by the default user to
        // have their corresponding artist and album owned by the same user.
        DB::table('songs')
            ->join('albums', 'songs.album_id', '=', 'albums.id')
            ->join('artists', 'albums.artist_id', '=', 'artists.id')
            ->whereNull('songs.podcast_id')
            ->where('songs.owner_id', '!=', $firstAdmin->id)
            ->orderBy('songs.created_at')
            ->select(
                'songs.*',
                'artists.name as artist_name',
                'artists.image as artist_image',
                'albums.name as album_name',
                'albums.cover as album_cover',
            )
            ->chunk(100, static function ($songsChunk) use (&$artistCache, &$albumCache): void {
                if (count($songsChunk) === 0) {
                    return;
                }

                $artistCases = [];
                $albumCases = [];
                $songIds = [];

                /** @var Song $song */
                foreach ($songsChunk as $song) {
                    $artistKey = "{$song->artist_name}|{$song->owner_id}";

                    $artistId = $artistCache[$artistKey]
                        ?? tap(DB::table('artists')->insertGetId([
                            'name' => $song->artist_name,
                            'public_id' => Ulid::generate(),
                            'user_id' => $song->owner_id,
                            'image' => $song->artist_image ?? '',
                        ]), static function ($id) use (&$artistCache, $artistKey): void {
                            $artistCache[$artistKey] = $id;
                        });

                    $albumKey = "{$song->album_name}|{$artistId}|{$song->owner_id}";

                    $albumId = $albumCache[$albumKey]
                        ?? tap(DB::table('albums')->insertGetId([
                            'name' => $song->album_name,
                            'public_id' => Ulid::generate(),
                            'artist_id' => $artistId,
                            'user_id' => $song->owner_id,
                            'cover' => $song->album_cover ?? '',
                        ]), static function ($id) use (&$albumCache, $albumKey): void {
                            $albumCache[$albumKey] = $id;
                        });

                    $songIds[] = $song->id;

                    $artistCases[$song->id] = $artistId;
                    $albumCases[$song->id] = $albumId;
                }

                // Build CASE statements for artist_id
                $artistCaseSql = 'CASE id ';

                foreach ($artistCases as $songId => $artistId) {
                    $artistCaseSql .= "WHEN '{$songId}' THEN {$artistId} ";
                }

                $artistCaseSql .= 'ELSE artist_id END';

                // Build CASE statements for album_id
                $albumCaseSql = 'CASE id ';

                foreach ($albumCases as $songId => $albumId) {
                    $albumCaseSql .= "WHEN '{$songId}' THEN {$albumId} ";
                }

                $albumCaseSql .= 'ELSE album_id END';

                // Run single batch update query
                DB::table('songs')
                    ->whereIn('id', $songIds)
                    ->update([
                        'artist_id' => DB::raw($artistCaseSql),
                        'album_id' => DB::raw($albumCaseSql),
                    ]);
            });
    }
};
