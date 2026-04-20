<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            $table->boolean('is_locked')->default(false)->after('rules');
        });
    }
};
