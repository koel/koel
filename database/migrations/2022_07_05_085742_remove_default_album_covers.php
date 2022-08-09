<?php

use App\Models\Album;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Album::query()->where('cover', 'unknown-album.png')->update(['cover' => '']);
    }
};
