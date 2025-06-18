<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // On GitHub Actions, MySQL sometimes falsely reports that the table does not exist (probably due to
        // some race condition with the database connection).
        if (!Schema::hasTable('playlist_collaborators')) {
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
