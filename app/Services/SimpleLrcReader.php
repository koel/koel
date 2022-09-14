<?php

namespace App\Services;

use Throwable;

class SimpleLrcReader
{
    public function tryReadForMediaFile(string $mediaFilePath): string
    {
        $lrcFilePath = self::getLrcFilePath($mediaFilePath);

        try {
            return $lrcFilePath ? trim(file_get_contents($lrcFilePath)) : '';
        } catch (Throwable) {
            return '';
        }
    }

    private static function getLrcFilePath(string $mediaFilePath): ?string
    {
        foreach (['.lrc', '.LRC'] as $extension) {
            $lrcFilePath = preg_replace('/\.[^.]+$/', $extension, $mediaFilePath);

            if (is_file($lrcFilePath) && is_readable($lrcFilePath)) {
                return $lrcFilePath;
            }
        }

        return null;
    }
}
