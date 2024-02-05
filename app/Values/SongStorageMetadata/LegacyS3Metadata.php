<?php

namespace App\Values\SongStorageMetadata;

final class LegacyS3Metadata extends S3CompatibleMetadata
{
    public function supported(): bool
    {
        return true;
    }
}
