<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRulesIntoPlaylists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            $table->text('rules')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            $table->dropColumn('rules');
        });
    }
}
