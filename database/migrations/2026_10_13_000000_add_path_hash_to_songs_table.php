<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->string('path_hash', 32)->nullable()->index();
        });

        DB::table('songs')
            ->select(['id', 'path'])
            ->chunkById(1000, static function (Collection $songs): void {
                $cases = $songs->map(static fn (object $song): string => sprintf(
                    "WHEN '%s' THEN '%s'",
                    $song->id,
                    simple_hash($song->path),
                ))->implode(' ');

                DB::table('songs')
                    ->whereIn('id', $songs->pluck('id'))
                    ->update(['path_hash' => DB::raw("CASE id $cases END")]);
            });
    }
};
