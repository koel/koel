<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            // This migration is actually to fix a mistake that the original one was deleted.
            // Therefore, we just "try" it and ignore on error.
            try {
                Schema::disableForeignKeyConstraints();
                $table->dropForeign('songs_contributing_artist_id_foreign');
                $table->dropColumn('contributing_artist_id');
                Schema::enableForeignKeyConstraints();
            } catch (Throwable) {
            }
        });
    }
};
