<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transcodes', static function (Blueprint $table): void {
            $table->boolean('is_preview')->default(false);
        });

        Schema::table('transcodes', static function (Blueprint $table): void {
            $table->dropUnique(['song_id', 'bit_rate']);
            $table->unique(['song_id', 'bit_rate', 'is_preview']);
        });
    }
};
