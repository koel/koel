<?php

use App\Models\Playlist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('playlist_song', static function (Blueprint $table): void {
                $table->dropForeign(['playlist_id']);
            });
        }

        Schema::table('playlists', static function (Blueprint $table): void {
            $table->string('id', 36)->change();
        });

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->string('playlist_id', 36)->change();
            $table->foreign('playlist_id')->references('id')->on('playlists')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Playlist::all()->each(static function (Playlist $playlist): void {
            $oldId = $playlist->id;
            $newId = Str::uuid()->toString();

            $playlist->id = $newId;
            $playlist->save();

            DB::table('playlist_song')->where('playlist_id', $oldId)->update([
                'playlist_id' => $newId,
            ]);
        });

        Schema::enableForeignKeyConstraints();
    }
};
