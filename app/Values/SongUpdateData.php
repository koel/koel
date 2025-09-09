<?php

namespace App\Values;

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
