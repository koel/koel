<?php

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArtistTableSeeder extends Seeder
{
    public function run(): void
    {
        Artist::firstOrCreate([
            'id' => Artist::UNKNOWN_ID,
        ], [
            'name' => Artist::UNKNOWN_NAME,
        ]);

        self::maybeResetPgsqlSerialValue();
    }

    private static function maybeResetPgsqlSerialValue(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('artists', 'id'), coalesce(max(id), 0) + 1, false) FROM artists"
            );
        }
    }
}
