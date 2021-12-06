<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreaseStringColumnsLength extends Migration
{
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->string('name', pow(2, 16) - 32)->change();
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->string('name', pow(2, 16) - 32)->change();
        });

        Schema::table('playlists', static function (Blueprint $table): void {
            $table->string('name', pow(2, 16) - 32)->change();
        });

        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('title', pow(2, 16) - 32)->change();
        });
    }
}
