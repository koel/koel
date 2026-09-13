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
        $transcode = $this->findTranscode($song, $bitRate);

        if ($transcode?->isValid()) {
            return $transcode->location;
        }

        // If a transcode record exists, but is not valid (i.e., checksum failed), delete the associated file.
        if ($transcode) {
            File::delete($transcode->location);
        }

        // (Re)Transcode the song to the specified bit rate and either create a new transcode record or
        // update the existing one.
        $codec = $this->transcoder->preferredCodec();
        $destination = artifact_path(sprintf('transcodes/%d/%s.%s', $bitRate, Ulid::generate(), $codec->extension()));
        $this->transcoder->transcode($song->path, $destination, $bitRate, $codec);

        $this->createOrUpdateTranscode(
            $song,
            $destination,
            $bitRate,
            $codec,
            File::hash($destination),
            File::size($destination),
        );

        return $destination;
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        File::delete($location);
    }
}
