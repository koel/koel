<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', static function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('parent_id', 36)->nullable()->index();
            $table->text('path')->unique();
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
