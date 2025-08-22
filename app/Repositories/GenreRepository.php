<?php

namespace App\Repositories;

use App\Enums\PlayableType;
use App\Models\Genre;
use App\Models\Song;
use App\Models\User;
use App\Values\GenreSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** @extends Repository<Genre> */
class GenreRepository extends Repository
{
    /** @return Collection<GenreSummary>|array<array-key, GenreSummary> */
    public function getAllSummaries(?User $scopedUser = null): Collection
    {
        $genres = Genre::query()
            ->join('genre_song', 'genre_song.genre_id', '=', 'genres.id')
            ->join('songs', 'songs.id', '=', 'genre_song.song_id')
            ->accessibleBy($scopedUser ?? auth()->user())
            ->groupBy('genres.id', 'genres.name', 'genres.public_id')
            ->orderBy('genres.name')
            ->select(
                'genres.public_id',
                'genres.name',
                DB::raw('COUNT(songs.id) AS song_count'),
                DB::raw('SUM(songs.length) AS length')
            )
            ->get()
            ->map(
                static fn (object $genre) => GenreSummary::make(
                    publicId: $genre->public_id,
                    name: $genre->name,
                    songCount: $genre->song_count,
                    length: $genre->length
                )
            );

        $summaryForNoGenre = $this->getSummaryForNoGenre($scopedUser);

        // Only add the "No Genre" stats if there are indeed songs without a genre
        if ($summaryForNoGenre->songCount > 0) {
            $genres->unshift($summaryForNoGenre);
        }

        return $genres;
    }

    public function getSummaryForGenre(Genre $genre, ?User $scopedUser = null): GenreSummary
    {
        /** @var object $result */
        $result = Song::query(type: PlayableType::SONG, user: $scopedUser ?? auth()->user())
            ->accessible()
            ->join('genre_song', 'songs.id', '=', 'genre_song.song_id')
            ->join('genres', 'genre_song.genre_id', '=', 'genres.id')
            ->where('genres.id', $genre->id)
            ->groupBy('genres.public_id', 'genres.name')
            ->select(
                'genres.public_id',
                'genres.name',
                DB::raw('COUNT(songs.id) AS song_count'),
                DB::raw('SUM(songs.length) AS length')
            )
            ->firstOrFail();

        return GenreSummary::make(
            publicId: $result->public_id,
            name: $result->name,
            songCount: $result->song_count,
            length: $result->length
        );
    }

    public function getSummaryForNoGenre(?User $scopedUser = null): GenreSummary
    {
        /** @var object $result */
        $result = Song::query(type: PlayableType::SONG, user: $scopedUser ?? auth()->user())
            ->accessible()
            ->leftJoin('genre_song', 'songs.id', '=', 'genre_song.song_id')
            ->whereNull('genre_song.genre_id')
            ->select(
                DB::raw('COUNT(songs.id) AS song_count'),
                DB::raw('SUM(songs.length) AS length')
            )
            ->firstOrFail();

        return GenreSummary::make(
            publicId: Genre::NO_GENRE_PUBLIC_ID,
            name: Genre::NO_GENRE_NAME,
            songCount: (int) $result->song_count,
            length: (float) $result->length
        );
    }
}
