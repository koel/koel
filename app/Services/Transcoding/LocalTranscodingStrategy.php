<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use Illuminate\Support\Facades\File;
use Throwable;

class LocalTranscodingStrategy extends TranscodingStrategy
{
    protected function createTranscodeLocation(Song $song, int $bitRate, TranscodeCodec $codec): string
    {
        $destination = artifact_path(sprintf('transcodes/%d/%s.%s', $bitRate, Ulid::generate(), $codec->extension()));

        try {
            $this->transcodeAndUpsert($song, $song->path, $destination, $bitRate, $codec);
        } catch (Throwable $e) {
            File::delete($destination);

            throw $e;
        }

        return $destination;
    }

    public function deleteTranscodeFile(string $location, SongStorageType $storageType): void
    {
        File::delete($location);
    }
}
