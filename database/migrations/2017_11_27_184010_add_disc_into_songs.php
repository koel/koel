<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscIntoSongs extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->integer('disc')->after('track')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropColumn('disc');
        });
    }
}
