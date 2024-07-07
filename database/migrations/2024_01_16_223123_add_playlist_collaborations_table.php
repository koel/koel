<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_collaborators', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('playlist_id', 36);
            $table->timestamps();
        });

        Schema::table('playlist_collaborators', static function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('playlist_id')->references('id')->on('playlists')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
