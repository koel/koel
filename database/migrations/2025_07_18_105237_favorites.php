<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("SET sql_mode = ''");
        }

        Schema::create('favorites', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('favoriteable_id', 36);
            $table->string('favoriteable_type')->default('playable');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'favoriteable_id', 'favoriteable_type']);
            $table->index(['user_id', 'favoriteable_type']);
        });

        DB::table('interactions')
            ->where('liked', true)
            ->orderBy('created_at')
            ->each(static function (object $favorite): void {
                DB::table('favorites')
                    ->insert([
                        'user_id' => $favorite->user_id,
                        'favoriteable_id' => $favorite->song_id,
                        'favoriteable_type' => 'playable',
                        'created_at' => $favorite->created_at,
                    ]);
            });

        Schema::table('interactions', static function (Blueprint $table): void {
            $table->dropColumn('liked');
        });

        Schema::table('favorites', static function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
