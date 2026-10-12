<?php

namespace App\Observers;

use App\Models\Theme;
use App\Services\Image\ImageStorage;

class ThemeObserver
{
    public function __construct(
        private readonly ImageStorage $imageStorage,
    ) {}

    public function deleted(Theme $theme): void
    {
        rescue_if($theme->properties->bgImage, function () use ($theme): void {
            $this->imageStorage->delete([$theme->properties->bgImage, $theme->thumbnail]);
        });
    }
}
