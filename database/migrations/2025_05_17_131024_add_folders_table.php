<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (env('GITHUB_ACTIONS')) {
            Schema::dropIfExists('folders'); // somehow MySQL yields an error but only on GitHub Actions
        }

        Schema::create('folders', static function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('parent_id', 36)->nullable()->index();
            // no need to set a unique index here, as indexing text columns or using long varchar is unnecessarily
            // complicated across different database systems
            $table->text('path');
        });

        Schema::table('folders', static function (Blueprint $table): void {
            $table->foreign('parent_id')->on('folders')->references('id')->cascadeOnDelete();
        });

        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('folder_id', 36)->nullable()->index();
            $table->foreign('folder_id')->on('folders')->references('id')->nullOnDelete();
        });
    }
};
