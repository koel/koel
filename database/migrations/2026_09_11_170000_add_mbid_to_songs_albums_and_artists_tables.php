<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['songs', 'albums', 'artists'] as $table) {
            Schema::table($table, static function (Blueprint $table): void {
                $table->string('mbid', 36)->nullable()->index();
            });
        }
    }
};
