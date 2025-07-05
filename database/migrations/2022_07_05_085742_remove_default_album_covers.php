<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('albums')->where('cover', 'unknown-album.png')->update(['cover' => '']);
    }
};
