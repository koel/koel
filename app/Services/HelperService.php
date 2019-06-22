<?php

namespace App\Services;

class HelperService
{
    /**
     * Get a unique hash from a file path.
     * This hash can then be used as the Song record's ID.
     */
    public function getFileHash(string $path): string
    {
        return md5(config('app.key').$path);
    }
}
