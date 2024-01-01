<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_states', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->json('song_ids');
            $table->string('current_song_id', 36)->nullable();
            $table->unsignedInteger('playback_position')->default(0);
            $table->timestamps();
        });

        Schema::table('queue_states', static function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('current_song_id')->references('id')->on('songs')->cascadeOnUpdate()->nullOnDelete();
        });
    }
};
