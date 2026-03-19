<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->index();
        });

        // Backfill positions per user, ordered by created_at
        DB::table('favorites')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id')
            ->each(static function (int $userId): void {
                DB::table('favorites')
                    ->where('user_id', $userId)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->pluck('id')
                    ->each(static function (int $id, int $index): void {
                        DB::table('favorites')->where('id', $id)->update(['position' => $index]);
                    });
            });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
