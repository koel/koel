<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlbumTableSeeder extends Seeder
{
    public function run(): void
    {
        self::maybeResetPgsqlSerialValue();
    }

    private static function maybeResetPgsqlSerialValue(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('albums', 'id'), coalesce(max(id), 0) + 1, false) FROM albums"
            );
        }
    }
}
