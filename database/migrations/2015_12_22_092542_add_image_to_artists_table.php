<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddImageToArtistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->string('image')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->dropColumn('image');
        });
    }
}
