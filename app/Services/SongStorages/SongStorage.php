<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\KoelPlusRequiredException;
use App\Helpers\Ulid;
use App\Models\User;
use App\Values\UploadReference;

abstract class SongStorage
{
    abstract public function getStorageType(): SongStorageType;

    abstract public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference;

    abstract public function undoUpload(UploadReference $reference): void;

    abstract public function delete(string $location, bool $backup = false): void;

    abstract public function testSetup(): void;

    abstract public function getLocalPath(string $location): string;

    public function assertSupported(): void
    {
        throw_unless(
            $this->getStorageType()->supported(),
            new KoelPlusRequiredException('The storage driver is only supported in Koel Plus.'),
        );
    }

    protected function generateStorageKey(string $filename, User $uploader): string
    {
        $name = basename(str_replace('\\', '/', $filename));

        return sprintf('%s__%s__%s', $uploader->public_id, Ulid::generate(), $name);
    }
}
