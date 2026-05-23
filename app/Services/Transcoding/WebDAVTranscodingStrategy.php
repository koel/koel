<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Services\SongStorages\WebDAVStorage;
use Illuminate\Support\Facades\File;

class WebDAVTranscodingStrategy extends TranscodingStrategy
{
    public function getTranscodeLocation(Song $song, int $bitRate): string
    {
        $transcode = $this->findTranscodeBySongAndBitRate($song, $bitRate);

        if ($transcode?->isValid()) {
            return $transcode->location;
        }

        if ($transcode) {
            File::delete($transcode->location);
        }

        /** @var WebDAVStorage $storage */
        $storage = app(WebDAVStorage::class);
        $tmpSource = $storage->copyToLocal($song->storage_metadata->getPath());

        try {
            $destination = artifact_path(sprintf('transcodes/%d/%s.m4a', $bitRate, Ulid::generate()));
            $this->transcoder->transcode($tmpSource, $destination, $bitRate);

            $this->createOrUpdateTranscode(
                $song,
                $destination,
                $bitRate,
                File::hash($destination),
                File::size($destination),
            );
        } finally {
            File::delete($tmpSource);
        }

        return $destination;
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        File::delete($location);
    }
}
