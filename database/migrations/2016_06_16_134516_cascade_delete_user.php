<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CascadeDeleteUser extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') { // @phpstan-ignore-line
                $table->dropForeign('playlists_user_id_foreign');
            }

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('playlists', static function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') { // @phpstan-ignore-line
                $table->dropForeign('playlists_user_id_foreign');
            }

            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}
