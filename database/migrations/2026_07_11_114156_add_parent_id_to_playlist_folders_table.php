<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('playlist_folders', static function (Blueprint $table): void {
            $table->string('parent_id', 36)->nullable()->index();
            $table->foreign('parent_id')->references('id')->on('playlist_folders')->cascadeOnUpdate()->nullOnDelete();
        });
    }
};
