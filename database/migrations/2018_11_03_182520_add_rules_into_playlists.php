<?php

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
