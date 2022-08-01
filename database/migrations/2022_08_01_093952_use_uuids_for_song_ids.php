<?php

use App\Models\Song;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('id', 36)->change();
        });

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->string('song_id', 36)->change();

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('playlist_song_song_id_foreign');
            }

            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->string('song_id', 36)->change();

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('interactions_song_id_foreign');
            }

            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Song::all()->each(static function (Song $song): void {
            $song->id = Str::uuid();
            $song->save();
        });
    }
};
