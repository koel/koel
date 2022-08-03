<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            if (DB::getDriverName() === 'sqlite') {
                $table->index('title');
            } else {
                $table->fullText('title');
            }

            $table->index(['track', 'disc']);
        });

        Schema::table('albums', static function (Blueprint $table): void {
            if (DB::getDriverName() === 'sqlite') {
                $table->index('name');
            } else {
                $table->fullText('name');
            }
        });
    }
};
