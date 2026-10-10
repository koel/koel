<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->text('description')->nullable();
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->text('description')->nullable();
        });
    }
};
