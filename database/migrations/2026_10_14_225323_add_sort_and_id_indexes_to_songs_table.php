<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * InnoDB already appends the primary key to every index. Neither InnoDB nor PostgreSQL can index long titles.
     */
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $columns = DB::getDriverName() === 'pgsql'
            ? ['year', 'length', 'created_at']
            : ['title', 'year', 'length', 'created_at'];

        Schema::table('songs', static function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column) {
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
