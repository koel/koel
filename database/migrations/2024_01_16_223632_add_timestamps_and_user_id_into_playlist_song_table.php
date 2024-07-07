<?php

use App\Models\Playlist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        Playlist::query()->get()->each(static function (Playlist $playlist): void {
            DB::table('playlist_song')->where('playlist_id', $playlist->id)->update([
                'user_id' => $playlist->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->unsignedInteger('user_id')->nullable(false)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
};
