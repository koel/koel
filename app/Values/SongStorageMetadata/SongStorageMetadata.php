<?php

namespace App\Values\SongStorageMetadata;

interface SongStorageMetadata
{
    public function supported(): bool;

    public function getPath(): string;
}
