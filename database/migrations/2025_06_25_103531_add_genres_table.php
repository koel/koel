<?php

use App\Helpers\Ulid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('genres', static function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('name');
        });

        Schema::create('genre_song', static function (Blueprint $table): void {
            $table->id();
            $table->string('song_id', 36)->index();
            $table->unsignedBigInteger('genre_id')->index();

            $table->foreign('song_id')->references('id')->on('songs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('genre_id')->references('id')->on('genres')->cascadeOnDelete()->cascadeOnUpdate();
        });

        self::migrateExistingData();

        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropColumn('genre');
        });
    }

    private static function migrateExistingData(): void
    {
        DB::table('songs')
            ->whereNotNull('genre')
            ->where('genre', '!=', '')
            ->get()
            ->each(static function ($song): void {
                $genres = collect(explode(',', $song->genre))
                    ->map(static fn ($genre) => trim($genre))
                    ->filter() // remove empty strings
                    ->unique();

                if ($genres->isEmpty()) {
                    return;
                }

                $songGenres = [];

                foreach ($genres as $name) {
                    $genreId = DB::table('genres')->where('name', $name)->first()?->id
                        ?: DB::table('genres')->insertGetId([
                            'public_id' => Ulid::generate(),
                            'name' => $name,
                        ]);

                    $songGenres[] = [
                        'song_id' => $song->id,
                        'genre_id' => $genreId,
                    ];
                }

                DB::table('genre_song')->insert($songGenres);
            });
    }
};
