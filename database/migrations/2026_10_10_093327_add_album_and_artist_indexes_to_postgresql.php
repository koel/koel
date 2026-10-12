<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('songs', static function (Blueprint $table): void {
            $table->index('album_id');
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->index('artist_id');
        });
    }
};
