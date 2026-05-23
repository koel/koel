<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\Transcode;
use App\Repositories\TranscodeRepository;
use Illuminate\Support\Facades\File;

abstract class TranscodingStrategy
{
    public function __construct(
        protected TranscodeRepository $transcodeRepository,
        protected Transcoder $transcoder,
    ) {}

    protected function findTranscodeBySongAndBitRate(Song $song, int $bitRate): ?Transcode
    {
        return $this->transcodeRepository->findFirstWhere([
            'song_id' => $song->id,
            'bit_rate' => $bitRate,
        ]);
    }

    protected function createOrUpdateTranscode(
        Song $song,
        string $locationOrCloudKey,
        int $bitRate,
        string $hash,
        int $fileSize,
    ): Transcode {
        Transcode::query()->upsert(
            values: [
                'song_id' => $song->id,
                'location' => $locationOrCloudKey,
                'bit_rate' => $bitRate,
                'hash' => $hash,
                'file_size' => $fileSize,
            ],
            uniqueBy: ['song_id', 'bit_rate'],
            update: ['location', 'hash', 'file_size'],
        );

        return $this->findTranscodeBySongAndBitRate($song, $bitRate); // @phpstan-ignore-line
    }

    protected function transcodeAndUpsert(Song $song, string $tmpSource, string $destination, int $bitRate): void
    {
        $this->transcoder->transcode($tmpSource, $destination, $bitRate);

        $this->createOrUpdateTranscode(
            $song,
            $destination,
            $bitRate,
            File::hash($destination),
            File::size($destination),
        );
    }

    abstract public function getTranscodeLocation(Song $song, int $bitRate): string;

    abstract public function deleteTranscodeFile(string $location, SongStorageType $storageType): void;
}
