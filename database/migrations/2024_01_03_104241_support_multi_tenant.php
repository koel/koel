<?php

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->unsignedInteger('owner_id')->nullable();
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('is_public')->default(false)->index();
        });

        Schema::table('songs', static function (Blueprint $table): void {
            $firstAdmin = User::query()->where('is_admin', true)->oldest()->first();

            if ($firstAdmin === null) {
                return;
            }

            // make sure all existing songs are accessible by all users and assuming the first admin "owns" them
            Song::query()->update([
                'is_public' => true,
                'owner_id' => $firstAdmin->id,
            ]);

            $table->unsignedInteger('owner_id')->nullable(false)->change();
        });
    }
};
