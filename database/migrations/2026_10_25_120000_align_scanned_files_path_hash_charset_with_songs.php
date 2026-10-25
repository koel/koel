<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * `scanned_files.path_hash` is created as ASCII, while `songs.path_hash` takes the table default (utf8mb4 on a
     * standard Laravel install). On MySQL and MariaDB, comparing two columns with different character sets converts the
     * ASCII side, which makes the `(scan_id, path_hash)` index unusable. The post-scan "songs that are not in this scan"
     * lookup then reads every scanned file once for every song, which takes minutes to hours on a large library.
     * Giving `scanned_files.path_hash` the character set and collation of `songs.path_hash` lets the index work again.
     */
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || !Schema::hasTable('scanned_files')) {
            return;
        }

        $songs = DB::selectOne(
            'SELECT character_set_name AS charset, collation_name AS collation FROM information_schema.columns '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            ['songs', 'path_hash'],
        );

        if (!$songs || !$songs->charset || !$songs->collation) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE scanned_files MODIFY path_hash VARCHAR(32) CHARACTER SET %s COLLATE %s NOT NULL',
            $songs->charset,
            $songs->collation,
        ));
    }
};
