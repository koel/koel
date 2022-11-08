<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions', static function (Blueprint $table): void {
            $table->timestamp('last_played_at')->nullable();
        });

        DB::statement('UPDATE interactions SET last_played_at = updated_at');

        DB::statement(
            "UPDATE playlists SET rules = REPLACE(rules, 'interactions.updated_at', 'interactions.last_played_at')"
        );
    }
};
