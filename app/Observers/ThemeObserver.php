<?php

namespace App\Observers;

use App\Models\Theme;
use Illuminate\Support\Facades\File;

class ThemeObserver
{
    public function deleted(Theme $theme): void
    {
        rescue_if(
            $theme->properties->bgImage,
            static function () use ($theme): void {
                File::delete([
                    image_storage_path($theme->properties->bgImage),
                    image_storage_path($theme->thumbnail),
                ]);
            },
        );
    }
}
