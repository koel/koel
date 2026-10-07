<?php

namespace App\Jobs;

use App\Models\Song;
use App\Models\User;
use App\Responses\SongUploadFailedResponse;
use App\Services\SongStorages\SongStorage;
use App\Services\Upload\UploadService;
use App\Values\UploadReference;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Throwable;

class HandlePresignedSongUploadJob extends QueuedJob
{
    public function __construct(
        public readonly string $location,
        public readonly string $uploadKey,
        public readonly User $uploader,
        public readonly ?string $processingLockOwner = null,
    ) {}

    public function handle(SongStorage $storage, UploadService $uploadService): Song
    {
        $reference = UploadReference::make(
            location: $this->location,
            localPath: $storage->getLocalPath($this->location),
        );

        $song = $uploadService->handleStoredUpload($reference, $this->uploader);

        if ($this->wasQueued()) {
            broadcast($uploadService->makeUploadResponse($song, $this->uploader, $this->uploadKey));
        }

        return $song;
    }

    public static function makeProcessingLock(string $location, ?string $owner = null): Lock
    {
        return Cache::lock('presigned-upload:' . simple_hash($location), 3600, $owner);
    }

    public function failed(Throwable $exception): void
    {
        self::makeProcessingLock($this->location, $this->processingLockOwner)->release();

        broadcast(SongUploadFailedResponse::make(
            uploader: $this->uploader,
            uploadKey: $this->uploadKey,
            message: $exception->getMessage(),
        ));
    }
}
