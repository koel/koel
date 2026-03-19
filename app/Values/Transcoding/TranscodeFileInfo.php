<?php

namespace App\Values\Transcoding;

use App\Enums\SongStorageType;
use App\Models\Transcode;

final readonly class TranscodeFileInfo
{
    public function __construct(
        public string $location,
        public SongStorageType $storage,
    ) {}

    public static function make(string $location, SongStorageType $storageType): self
    {
        return new self($location, $storageType);
    }

    public static function fromTranscode(Transcode $transcode): self
    {
        return self::make($transcode->location, $transcode->storage);
    }
}
