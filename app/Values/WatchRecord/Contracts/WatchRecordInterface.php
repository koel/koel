<?php

namespace App\Values\WatchRecord\Contracts;

interface WatchRecordInterface
{
    public function parse(string $string): void;

    public function getPath(): string;

    public function isDeleted(): bool;

    public function isNewOrModified(): bool;

    public function isDirectory(): bool;

    public function isFile(): bool;
}
