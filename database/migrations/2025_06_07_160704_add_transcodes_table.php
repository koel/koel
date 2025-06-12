<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transcodes', static function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('song_id')->index();
            $table->integer('bit_rate');
            $table->text('location');
            $table->string('hash', 32);
            $table->unique(['song_id', 'bit_rate']);
            $table->timestamps();

            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
