<?php

namespace Tests\Integration\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlignScannedFilesPathHashCharsetWithSongsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            self::markTestSkipped('Character sets only matter on MySQL and MariaDB.');
        }
    }

    #[Test]
    public function bothPathHashColumnsShareTheirCharsetAndCollation(): void
    {
        self::assertSame(self::pathHashEncoding('songs'), self::pathHashEncoding('scanned_files'));
    }

    #[Test]
    public function anAsciiColumnIsAlignedWithTheSongsColumn(): void
    {
        DB::statement(
            'ALTER TABLE scanned_files MODIFY path_hash VARCHAR(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL',
        );

        self::assertNotSame(self::pathHashEncoding('songs'), self::pathHashEncoding('scanned_files'));

        $this->runMigration();

        self::assertSame(self::pathHashEncoding('songs'), self::pathHashEncoding('scanned_files'));
    }

    /** @return array{string, string} The character set and the collation of `$table.path_hash`. */
    private static function pathHashEncoding(string $table): array
    {
        $column = DB::selectOne('SELECT character_set_name AS charset, collation_name AS collation FROM information_schema.columns '
        . 'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, 'path_hash']);

        return [$column->charset, $column->collation];
    }

    private function runMigration(): void
    {
        /** @var Migration $migration */
        $migration = require
            database_path('migrations/2026_10_25_120000_align_scanned_files_path_hash_charset_with_songs.php');

        $migration->up(); // @phpstan-ignore-line
    }
}
