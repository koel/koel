<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameContributingArtistId extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['contributing_artist_id']);
            }

            $table->renameColumn('contributing_artist_id', 'artist_id');
            $table->foreign('artist_id')->references('id')->on('artists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['contributing_artist_id']);
            }

            $table->renameColumn('artist_id', 'contributing_artist_id');
            $table->foreign('artist_id')->references('id')->on('artists')->onDelete('cascade');
        });
    }
}
