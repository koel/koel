<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use Illuminate\Support\Facades\File;

class LocalTranscodingStrategy extends TranscodingStrategy
{
    public function getTranscodeLocation(Song $song, int $bitRate): string
    {
        $transcode = $this->findTranscodeBySongAndBitRate($song, $bitRate);

        if ($transcode?->isValid()) {
            return $transcode->location;
        }

        // If a transcode record exists, but is not valid (i.e., checksum failed), delete the associated file.
        if ($transcode) {
            File::delete($transcode->location);
        }

        // (Re)Transcode the song to the specified bit rate and either create a new transcode record or
        // update the existing one.
        $destination = artifact_path(sprintf('transcodes/%d/%s.m4a', $bitRate, Ulid::generate()));
        $this->transcoder->transcode($song->path, $destination, $bitRate);
        $this->createOrUpdateTranscode($song, $destination, $bitRate, File::hash($destination));

        return $destination;
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        File::delete($location);
    }
}
