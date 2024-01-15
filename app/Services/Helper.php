<?php

namespace App\Services;

use SplFileInfo;

class Helper
{
    public static function getModifiedTime(string|SplFileInfo $file): int
    {
        $file = is_string($file) ? new SplFileInfo($file) : $file;

        // Workaround for #344, where getMTime() fails for certain files with Unicode names on Windows.
        return attempt(static fn () => $file->getMTime()) ?? time();
    }
}
