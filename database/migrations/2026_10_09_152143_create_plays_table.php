<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plays', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('song_id', 36);
            $table->timestamp('played_at');

            $table->index(['user_id', 'played_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
