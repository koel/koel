<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('podcasts', static function (Blueprint $table): void {
            $table->text('categories')->change();
            $table->text('metadata')->change();
        });

        Schema::table('queue_states', static function (Blueprint $table): void {
            $table->text('song_ids')->change();
        });

        Schema::table('licenses', static function (Blueprint $table): void {
            $table->text('instance')->nullable()->change();
            $table->text('meta')->nullable()->change();
        });

        Schema::table('songs', static function (Blueprint $table): void {
            $table->text('episode_metadata')->nullable()->change();
        });

        Schema::table('podcast_user', static function (Blueprint $table): void {
            $table->text('state')->nullable()->change();
        });
    }
};
