<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeArtistNameLength extends Migration
{
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->dropIndex('artists_name_unique');
            $table->text('name')->change();
            $table->unique(['name']);
        });
    }

    public function down(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->string('name', 191)->change();
        });
    }
}
