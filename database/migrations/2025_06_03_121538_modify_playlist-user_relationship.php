<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Idempotent: skip when the rename has already been applied. Checking the target rather
        // than the source means a false-negative on `hasTable('playlist_collaborators')` no longer
        // silently no-ops; the rename below will surface a real error instead. See issue #2019.
        if (Schema::hasTable('playlist_user')) {
            return;
        }

        Schema::table('playlist_collaborators', static function (Blueprint $table): void {
            $table->string('role')->default('collaborator');
            $table->integer('position')->default(0);
        });

        DB::table('playlists')->get()->each(static function ($playlist): void {
            DB::table('playlist_collaborators')->insert([
                'user_id' => $playlist->user_id,
                'playlist_id' => $playlist->id,
                'role' => 'owner',
            ]);
        });

        Schema::table('playlist_collaborators', static function (Blueprint $table): void {
            $table->rename('playlist_user');
        });

        Schema::table('playlists', static function (Blueprint $table): void {
            Schema::withoutForeignKeyConstraints(static function () use ($table): void {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        });
    }
};
