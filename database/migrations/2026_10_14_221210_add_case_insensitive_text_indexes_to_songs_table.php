<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        foreach (['title', 'artist_name', 'album_name'] as $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS songs_{$column}_nocase_index ON songs ($column COLLATE NOCASE)");
        }
    }
};
