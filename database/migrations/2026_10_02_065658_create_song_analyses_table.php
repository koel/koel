<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('song_analyses', static function (Blueprint $table): void {
            $table->string('song_id')->primary();
            $table->float('loudness');
            $table->float('true_peak');
            $table->json('levels');
            $table->timestamps();

            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
