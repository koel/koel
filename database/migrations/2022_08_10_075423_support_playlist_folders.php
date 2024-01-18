<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_folders', static function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('name');
            $table->unsignedInteger('user_id');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('playlists', static function (Blueprint $table): void {
            $table->string('folder_id', 36)->nullable();
            $table->foreign('folder_id')
                ->references('id')
                ->on('playlist_folders')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }
};
