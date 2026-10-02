<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->float('loudness')->nullable();
            $table->float('true_peak')->nullable();
        });

        Schema::create('song_waveforms', static function (Blueprint $table): void {
            $table->string('song_id')->primary();
            $table->json('levels');
            $table->timestamps();

            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
