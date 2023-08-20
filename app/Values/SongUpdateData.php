<?php

namespace App\DTO;

use App\Http\Requests\API\SongUpdateRequest;
use Illuminate\Contracts\Support\Arrayable;

final class SongUpdateData implements Arrayable
{
    private function __construct(
        public ?string $title,
        public ?string $artistName,
        public ?string $albumName,
        public ?string $albumArtistName,
        public ?int $track,
        public ?int $disc,
        public ?string $genre,
        public ?int $year,
        public ?string $lyrics,
    ) {
        $this->albumArtistName = $this->albumArtistName ?: $this->artistName;
    }

    public static function fromRequest(SongUpdateRequest $request): self
    {
        return new self(
            title: $request->input('data.title'),
            artistName: $request->input('data.artist_name'),
            albumName: $request->input('data.album_name'),
            albumArtistName: $request->input('data.album_artist_name'),
            track: (int) $request->input('data.track'),
            disc: (int) $request->input('data.disc'),
            genre: $request->input('data.genre'),
            year: (int) $request->input('data.year'),
            lyrics: $request->input('data.lyrics'),
        );
    }

    public static function make(
        ?string $title,
        ?string $artistName,
        ?string $albumName,
        ?string $albumArtistName,
        ?int $track,
        ?int $disc,
        ?string $genre,
        ?int $year,
        ?string $lyrics
    ): self {
        return new self(
            $title,
            $artistName,
            $albumName,
            $albumArtistName,
            $track,
            $disc,
            $genre,
            $year,
            $lyrics,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'artist' => $this->artistName,
            'album' => $this->albumName,
            'album_artist' => $this->albumArtistName,
            'track' => $this->track,
            'disc' => $this->disc,
            'genre' => $this->genre,
            'year' => $this->year,
            'lyrics' => $this->lyrics,
        ];
    }
}
