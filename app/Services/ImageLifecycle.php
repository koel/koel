<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageLifecycle
{
    public function onReplaced(?string $oldFilename, bool $hasThumbnail = false): void
    {
        $this->deleteFiles($oldFilename, $hasThumbnail);
    }

    public function onRemoved(?string $filename, bool $hasThumbnail = false): void
    {
        $this->deleteFiles($filename, $hasThumbnail);
    }

    private function deleteFiles(?string $filename, bool $hasThumbnail): void
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
