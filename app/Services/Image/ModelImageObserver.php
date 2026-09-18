<?php

namespace App\Services\Image;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ModelImageObserver
{
    private readonly ImageStorage $imageStorage;

    private function __construct(
        private readonly string $fieldName,
        private readonly bool $hasThumbnail,
    ) {
        $this->imageStorage = app(ImageStorage::class);
    }

    public static function make(string $fieldName, bool $hasThumbnail = false): self
    {
        return new self($fieldName, $hasThumbnail);
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
