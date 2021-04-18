<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicColumnToPlaylistsTable extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            $table->boolean('public')->default(false)->after('rules');
        });
    }
}
