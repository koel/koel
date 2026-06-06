<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->unique(['user_id', 'name'], 'artists_user_id_name_unique');
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->unique(['artist_id', 'name'], 'albums_artist_id_name_unique');
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('CREATE UNIQUE INDEX songs_path_unique ON songs (path(768))');
        } else {
            Schema::table('songs', static function (Blueprint $table): void {
                $table->unique('path', 'songs_path_unique');
            });
        }
    }
};
