<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * InnoDB already appends the primary key to every index, and can't index the long title column.
     */
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('songs', static function (Blueprint $table): void {
            foreach (['title', 'year', 'length', 'created_at'] as $column) {
                if (Schema::hasIndex('songs', "songs_{$column}_index")) {
                    $table->dropIndex("songs_{$column}_index");
                }

                if (!Schema::hasIndex('songs', "songs_{$column}_id_index")) {
                    $table->index([$column, 'id']);
                }
            }
        });
    }
};
