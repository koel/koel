<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('interactions', static function (Blueprint $table): void {
            $table->index(['song_id', 'user_id', 'last_played_at']);
        });
    }
};
