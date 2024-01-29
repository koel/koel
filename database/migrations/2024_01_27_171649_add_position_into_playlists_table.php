<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->unsignedInteger('position')->index()->default(0);
        });

        DB::table('playlists')->orderBy('id')->chunk(100, static function ($playlists): void {
            foreach ($playlists as $playlist) {
                DB::table('playlist_song')
                    ->where('playlist_id', $playlist->id)
                    ->update([
                        'position' => DB::raw("(SELECT COUNT(id)
                            FROM playlist_song p
                            WHERE p.playlist_id = '$playlist->id'
                            AND p.id <= playlist_song.id)"),
                    ]);
            }
        });
    }
};
