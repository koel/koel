<?php

namespace App\Services\Image;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelImageObserver
{
    private function __construct(
        private readonly string $fieldName,
        private readonly bool $hasThumbnail,
    ) {}

    public static function make(string $fieldName, bool $hasThumbnail = false): static
    {
        return new static($fieldName, $hasThumbnail);
    }

    public function onModelUpdating(Model $model): void
    {
        if ($model->isDirty($this->fieldName)) {
            $this->delete($model->getRawOriginal($this->fieldName));
        }
    }

    public function onModelDeleted(Model $model): void
    {
        $this->delete($model->getRawOriginal($this->fieldName));
    }

    private function delete(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $paths = [image_storage_path($filename)];

        if ($this->hasThumbnail) {
            $paths[] = image_storage_path(self::deriveThumbnailFilename($filename));
        }

        rescue(static fn () => File::delete($paths), report: false);
    }

    private static function deriveThumbnailFilename(string $filename): string
    {
        return sprintf('%s_thumb.%s', Str::beforeLast($filename, '.'), Str::afterLast($filename, '.'));
    }
}
