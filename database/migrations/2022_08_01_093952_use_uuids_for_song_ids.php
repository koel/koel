<?php

use App\Helpers\Uuid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        DB::table('songs')->get()->each(static function ($song): void {
            DB::table('songs')
                ->where('id', $song->id)
                ->update(['id' => Uuid::generate()]);
        });

        Schema::enableForeignKeyConstraints();
    }
};
