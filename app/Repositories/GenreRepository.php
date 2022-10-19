<?php

namespace App\Repositories;

use App\Models\Song;
use App\Values\Genre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenreRepository
{
    /** @return Collection|array<array-key, Genre> */
    public function getAll(): Collection
    {
        return Song::query()
            ->select('genre', DB::raw('COUNT(id) AS song_count'), DB::raw('SUM(length) AS length'))
            ->groupBy('genre')
            ->orderBy('genre')
            ->get()
            ->transform(static fn (object $record): Genre => Genre::make(
                name: $record->genre ?: Genre::NO_GENRE,
                songCount: $record->song_count,
                length: $record->length
            ));
    }

    public function getOne(string $name): ?Genre
    {
        /** @var object|null $record */
        $record = Song::query()
            ->select('genre', DB::raw('COUNT(id) AS song_count'), DB::raw('SUM(length) AS length'))
            ->groupBy('genre')
            ->where('genre', $name === Genre::NO_GENRE ? '' : $name)
            ->first();

        return $record
            ? Genre::make(
                name: $record->genre ?: Genre::NO_GENRE,
                songCount: $record->song_count,
                length: $record->length
            )
            : null;
    }
}
