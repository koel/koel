<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->unsignedBigInteger('file_size')->nullable();
        });

        Schema::table('transcodes', static function (Blueprint $table): void {
            $table->unsignedBigInteger('file_size')->nullable();
        });
    }
};
