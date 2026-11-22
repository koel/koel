<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hidden_songs', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('song_id', 36);
            $table->timestamps();

            $table->unique(['user_id', 'song_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};
