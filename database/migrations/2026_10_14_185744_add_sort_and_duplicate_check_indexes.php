<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            if (!Schema::hasIndex('songs', 'songs_year_index')) {
                $table->index('year');
            }

            if (!Schema::hasIndex('songs', 'songs_length_index')) {
                $table->index('length');
            }

            if (!Schema::hasIndex('songs', 'songs_owner_id_hash_index')) {
                $table->index(['owner_id', 'hash']);
            }
        });
    }
};
