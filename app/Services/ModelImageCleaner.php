<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelImageCleaner
{
    public function delete(?string $filename, bool $hasThumbnail = false): void
    {
        if (!$filename) {
            return;
        }

        $paths = [image_storage_path($filename)];

        if ($hasThumbnail) {
            $paths[] = image_storage_path(self::deriveThumbnailFilename($filename));
        }

        rescue(static fn () => File::delete($paths));
    }

    private static function deriveThumbnailFilename(string $filename): string
    {
        return sprintf('%s_thumb.%s', Str::beforeLast($filename, '.'), Str::afterLast($filename, '.'));
    }
}
