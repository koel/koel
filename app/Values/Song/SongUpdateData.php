<?php

namespace App\Values\Song;

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

    public static function make(
        ?string $title = null,
        ?string $artistName = null,
        ?string $albumName = null,
        ?string $albumArtistName = null,
        ?int $track = null,
        ?int $disc = null,
        ?string $genre = null,
        ?int $year = null,
        ?string $lyrics = null,
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
