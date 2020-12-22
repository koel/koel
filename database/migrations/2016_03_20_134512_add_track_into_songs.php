<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrackIntoSongs extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->integer('track')->after('length')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropColumn('track');
        });
    }
}
