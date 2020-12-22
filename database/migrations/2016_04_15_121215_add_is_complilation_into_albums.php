<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIsComplilationIntoAlbums extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::table('albums', static function (Blueprint $table): void {
            $table->boolean('is_compilation')->nullable()->defaults(false)->after('cover');
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
