<?php

namespace App\Values\Song;

use App\Enums\SongStorageType;
use App\Models\Song;

final readonly class SongFileInfo
{
    private function __construct(public string $location, public SongStorageType $storage)
    {
    }

    public static function make(string $location, SongStorageType $storage): self
    {
        return new self($location, $storage);
    }

    public static function fromSong(Song $song): self
    {
        return self::make($song->storage_metadata->getPath(), $song->storage);
    }
}
