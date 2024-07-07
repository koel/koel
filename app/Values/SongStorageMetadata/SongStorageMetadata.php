<?php

namespace App\Values\SongStorageMetadata;

abstract class SongStorageMetadata
{
    abstract public function getPath(): string;
}
