<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('albums', static function (Blueprint $table): void {
            $table->string('artist_ulid', 26)->nullable()->after('artist_id');
        });

        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('artist_ulid', 26)->nullable()->after('artist_id');
            $table->string('album_ulid', 26)->nullable()->after('album_id');
        });
    }
};
