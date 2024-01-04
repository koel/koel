<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Throwable;

class SimpleLrcReader
{
    public function tryReadForMediaFile(string $mediaFilePath): string
    {
        $lrcFilePath = self::getLrcFilePath($mediaFilePath);

        try {
            return $lrcFilePath ? trim(File::get($lrcFilePath)) : '';
        } catch (Throwable) {
            return '';
        }
    }

    private static function getLrcFilePath(string $mediaFilePath): ?string
    {
        foreach (['.lrc', '.LRC'] as $extension) {
            $lrcFilePath = preg_replace('/\.[^.]+$/', $extension, $mediaFilePath);

            if (File::isFile($lrcFilePath) && File::isReadable($lrcFilePath)) {
                return $lrcFilePath;
            }
        }

        return null;
    }
}
