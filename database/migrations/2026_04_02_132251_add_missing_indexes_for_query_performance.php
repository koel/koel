<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('interactions', static function (Blueprint $table): void {
            $table->index(['user_id', 'song_id']);
        });

        Schema::table('podcast_user', static function (Blueprint $table): void {
            $table->index(['user_id', 'podcast_id']);
        });

        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->index(['playlist_id', 'song_id']);
        });
    }
};
