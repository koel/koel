<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || !Schema::hasTable('scanned_files')) {
            return;
        }

        $sql =
            'SELECT character_set_name AS charset, collation_name AS collation FROM information_schema.columns '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?';

        $prefix = DB::getTablePrefix();

        $songs = DB::selectOne($sql, [$prefix . 'songs', 'path_hash']);

        if (!$songs || !$songs->charset || !$songs->collation) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY path_hash VARCHAR(32) CHARACTER SET %s COLLATE %s NOT NULL',
            $prefix . 'scanned_files',
            $songs->charset,
            $songs->collation,
        ));
    }
};
