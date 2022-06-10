<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->index('title');
            $table->index(['track', 'disc']);
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->index('name');
        });
    }
};
