<?php

namespace App\Values\Transcoding;

use App\Enums\SongStorageType;
use App\Models\Transcode;

class TranscodeFileInfo
{
    public function __construct(public readonly string $location, public readonly SongStorageType $storage)
    {
    }

    public static function make(string $location, SongStorageType $storageType): self
    {
        return new self($location, $storageType);
    }

    public static function fromTranscode(Transcode $transcode): self
    {
        return self::make($transcode->location, $transcode->storage);
    }
}
