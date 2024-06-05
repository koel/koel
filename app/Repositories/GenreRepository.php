<?php

namespace App\Repositories;

use App\Enums\PlayableType;
use App\Models\Song;
use App\Models\User;
use App\Values\Genre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenreRepository
{
    /** @return Collection|array<array-key, Genre> */
    public function getAll(?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? auth()->user())
            ->accessible()
            ->select('songs.genre', DB::raw('COUNT(songs.id) AS song_count'), DB::raw('SUM(songs.length) AS length'))
            ->groupBy('songs.genre')
            ->orderBy('songs.genre')
            ->get()
            ->transform(static fn (object $record): Genre => Genre::make( // @phpstan-ignore-line
                name: $record->genre ?: Genre::NO_GENRE, // @phpstan-ignore-line
                songCount: $record->song_count, // @phpstan-ignore-line
                length: $record->length // @phpstan-ignore-line
            ));
    }

    public function getOne(string $name, ?User $scopedUser = null): ?Genre
    {
        /** @var object|null $record */
        $record = Song::query(type: PlayableType::SONG, user: $scopedUser ?? auth()->user())
            ->accessible()
            ->select('songs.genre', DB::raw('COUNT(songs.id) AS song_count'), DB::raw('SUM(songs.length) AS length'))
            ->groupBy('songs.genre')
            ->where('songs.genre', $name === Genre::NO_GENRE ? '' : $name)
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
