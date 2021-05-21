<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlbumTableSeeder extends Seeder
{
    public function run(): void
    {
        Album::firstOrCreate([
            'id' => Album::UNKNOWN_ID,
        ], [
            'artist_id' => Artist::UNKNOWN_ID,
            'name' => Album::UNKNOWN_NAME,
            'cover' => Album::UNKNOWN_COVER,
        ]);

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
