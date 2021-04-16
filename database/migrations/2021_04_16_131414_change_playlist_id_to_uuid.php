<?php

use App\Models\Playlist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChangePlaylistIdToUuid extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('playlists', static function (Blueprint $table): void {
            $table->string('id', 36)->change();
        });

        Schema::table('playlist_song', static function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') { // @phpstan-ignore-line
                $table->dropForeign('playlist_song_playlist_id_foreign');
            }

            $table->string('playlist_id', 36)->change();
            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();

        Playlist::all()->each(static function (Playlist $playlist): void {
            $playlist->id = Str::uuid()->toString();
            $playlist->save();
        });
    }
}
