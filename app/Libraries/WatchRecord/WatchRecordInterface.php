<?php

namespace App\Libraries\WatchRecord;

interface WatchRecordInterface
{
    public function parse(string $string);

    public function getPath(): string;

    public function isDeleted(): bool;

    public function isNewOrModified(): bool;

    public function isDirectory(): bool;

    public function isFile(): bool;
}
