<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToArtistsTable extends Migration
{
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->string('image')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->dropColumn('image');
        });
    }
}
