<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddTrackIntoSongs extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->integer('track')->after('length')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropColumn('track');
        });
    }
}
