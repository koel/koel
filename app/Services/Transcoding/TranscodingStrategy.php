<?php

namespace App\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Models\Transcode;
use App\Repositories\TranscodeRepository;

abstract class TranscodingStrategy
{
    public function __construct(
        protected TranscodeRepository $transcodeRepository,
        protected Transcoder $transcoder,
    ) {
    }

    protected function findTranscodeBySongAndBitRate(Song $song, int $bitRate): ?Transcode
    {
        return $this->transcodeRepository->findFirstWhere([
            'song_id' => $song->id,
            'bit_rate' => $bitRate,
        ]);
    }

    protected function createOrUpdateTranscode(Song $song, string $location, int $bitRate, string $hash): Transcode
    {
        Transcode::query()->upsert([
            'song_id' => $song->id,
            'location' => $location,
            'bit_rate' => $bitRate,
            'hash' => $hash,
        ], ['song_id', 'bit_rate'], ['location', 'hash']);

        return $this->findTranscodeBySongAndBitRate($song, $bitRate); // @phpstan-ignore-line
    }

    abstract public function getTranscodeLocation(Song $song, int $bitRate): string;

    abstract public function deleteTranscodeFile(string $location, SongStorageType $storageType): void;
}
