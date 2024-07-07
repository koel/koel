<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_collaboration_tokens', static function (Blueprint $table): void {
            $table->id();
            $table->string('playlist_id', 36)->nullable(false);
            $table->string('token', 36)->unique();
            $table->timestamps();
        });

        Schema::table('playlist_collaboration_tokens', static function (Blueprint $table): void {
            $table->foreign('playlist_id')->references('id')->on('playlists')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
