<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('albums', static function (Blueprint $table): void {
            $table->smallInteger('year')->nullable();
        });

        DB::table('albums')
            ->join('songs', 'albums.id', '=', 'songs.album_id')
            ->whereNull('albums.year')
            ->whereNotNull('songs.year')
            ->groupBy('albums.id', 'songs.id', 'songs.year')
            ->distinct('albums.id')
            ->get(['albums.id', 'songs.year'])
            ->each(static function ($album): void {
                DB::table('albums')->where('id', $album->id)->update(['year' => $album->year]);
            });
    }
};
