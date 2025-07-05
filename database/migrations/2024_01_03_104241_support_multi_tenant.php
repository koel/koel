<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->unsignedInteger('owner_id')->nullable();
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('is_public')->default(false)->index();
        });

        Schema::table('songs', static function (): void {
            $firstAdmin = DB::table('users')->oldest()->first();

            if (!$firstAdmin) {
                return;
            }

            // make sure all existing songs are accessible by all users and assuming the first admin "owns" them
            DB::table('songs')->update([
                'owner_id' => $firstAdmin->id,
                'is_public' => true,
            ]);
        });
    }
};
