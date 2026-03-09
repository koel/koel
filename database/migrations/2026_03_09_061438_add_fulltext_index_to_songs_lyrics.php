<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite doesn't support fulltext indexes on regular columns
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('songs', static function (Blueprint $table): void {
            $table->fullText('lyrics');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropFullText('lyrics');
        });
    }
};
