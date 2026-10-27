<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Values\Transcoding\PreviewSection;
use Illuminate\Support\Facades\File;

class LocalTranscodingStrategy extends TranscodingStrategy
{
    protected function findOrCreateTranscodeLocation(
        Song $song,
        int $bitRate,
        ?string $localSourcePath,
        ?PreviewSection $previewSection,
    ): string {
        $transcode = $this->findTranscode($song, $bitRate, $previewSection);

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
        $this->transcoder->transcode($song->path, $destination, $bitRate, $previewSection);

        $this->createOrUpdateTranscode(
            $song,
            $destination,
            $bitRate,
            $previewSection,
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
