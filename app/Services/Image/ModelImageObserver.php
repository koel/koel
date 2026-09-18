<?php

namespace App\Services\Image;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ModelImageObserver
{
    private function __construct(
        private readonly ImageStorage $imageStorage,
        private readonly string $fieldName,
        private readonly bool $hasThumbnail,
    ) {}

    public static function make(ImageStorage $imageStorage, string $fieldName, bool $hasThumbnail = false): self
    {
        return new self($imageStorage, $fieldName, $hasThumbnail);
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

        $fileNames = [$filename];

        if ($this->hasThumbnail) {
            $fileNames[] = self::deriveThumbnailFilename($filename);
        }

        rescue(fn () => $this->imageStorage->delete($fileNames), report: false);
    }

    private static function deriveThumbnailFilename(string $filename): string
    {
        return sprintf('%s_thumb.%s', Str::beforeLast($filename, '.'), Str::afterLast($filename, '.'));
    }
}
