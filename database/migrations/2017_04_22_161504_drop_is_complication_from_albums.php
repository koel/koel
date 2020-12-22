<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropIsComplicationFromAlbums extends Migration
{
    public function up(): void
    {
        Schema::table('albums', static function (Blueprint $table): void {
            $table->dropColumn('is_compilation');
        });
    }

    public function down(): void
    {
    }
}
