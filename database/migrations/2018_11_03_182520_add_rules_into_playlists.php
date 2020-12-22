<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRulesIntoPlaylists extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            $table->text('rules')->after('name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            $table->dropColumn('rules');
        });
    }
}
