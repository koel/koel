<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_playlist_folder', static function (Blueprint $table): void {
            $table->string('folder_id', 36)->nullable(false);
            $table->string('playlist_id', 36)->nullable(false);
        });

        Schema::table('playlist_playlist_folder', static function (Blueprint $table): void {
            $table->foreign('folder_id')
                ->references('id')
                ->on('playlist_folders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unique(['folder_id', 'playlist_id']);
        });

        DB::table('playlists')->whereNotNull('folder_id')->get()->each(static function ($playlist): void {
            // There might be some data inconsistency, so we need to check if the folder exists first.
            if (DB::table('playlist_folders')->find($playlist->folder_id) === null) {
                return;
            }

            DB::table('playlist_playlist_folder')->insert([
                'folder_id' => $playlist->folder_id,
                'playlist_id' => $playlist->id,
            ]);
        });

        Schema::table('playlists', static function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['folder_id']);
            }

            $table->dropColumn('folder_id');
        });
    }
};
