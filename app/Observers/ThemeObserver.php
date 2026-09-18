<?php

namespace App\Observers;

use App\Models\Theme;
use App\Services\Image\ImageStorage;

class ThemeObserver
{
    public function deleted(Theme $theme): void
    {
        rescue_if($theme->properties->bgImage, static function () use ($theme): void {
            app(ImageStorage::class)->delete([$theme->properties->bgImage, $theme->thumbnail]);
        });
    }
}
