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
        Schema::disableForeignKeyConstraints();

        if (DB::getDriverName() !== 'sqlite') {
            collect(['playlist_song', 'interactions'])->each(static function (string $table): void {
                Schema::table($table, static function (Blueprint $table): void {
                    $table->dropForeign(['song_id']);
                });
            });
        }

        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('id', 36)->change();
        });

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->string('song_id', 36)->change();
            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->string('song_id', 36)->change();
            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Song::all()->each(static function (Song $song): void {
            $song->id = Str::uuid()->toString();
            $song->save();
        });

        Schema::enableForeignKeyConstraints();
    }
};
