<?php

namespace App\Values;

final readonly class UploadReference
{
    /**
     * @param string $location Depending on the storage type, this could be the full storage key (with prefix)
     * or the local path to the file. This is the value to be stored in the database.
     * @param string $localPath The local path to the (maybe tmp.) file, used for tag scanning or cleaning up.
     */
    private function __construct(public string $location, public string $localPath)
    {
    }

    public static function make(string $location, string $localPath): self
    {
        return new self($location, $localPath);
    }
}
