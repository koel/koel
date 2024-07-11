<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlist_song', static function (Blueprint $table): void {
            $table->unsignedInteger('position')->index()->default(0);
        });
    }
};
