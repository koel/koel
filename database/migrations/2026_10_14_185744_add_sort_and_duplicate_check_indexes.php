<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * These name columns are too long for a MySQL/MariaDB index key, and a prefix index can't serve an ORDER BY.
     *
     * @var array<string, string>
     */
    private const array LONG_NAME_COLUMNS = [
        'songs' => 'title',
        'albums' => 'name',
        'artists' => 'name',
    ];

    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            if (!Schema::hasIndex('songs', 'songs_year_index')) {
                $table->index('year');
            }

            if (!Schema::hasIndex('songs', 'songs_length_index')) {
                $table->index('length');
            }

            if (!Schema::hasIndex('songs', 'songs_owner_id_hash_index')) {
                $table->index(['owner_id', 'hash']);
            }
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::LONG_NAME_COLUMNS as $tableName => $column) {
            if (Schema::hasIndex($tableName, "{$tableName}_{$column}_index")) {
                continue;
            }

            Schema::table($tableName, static function (Blueprint $table) use ($column): void {
                $table->index($column);
            });
        }
    }
};
