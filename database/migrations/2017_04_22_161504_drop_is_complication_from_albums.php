<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropIsComplicationFromAlbums extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::table('albums', static function (Blueprint $table): void {
            $table->dropColumn('is_compilation');
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
    }
}
