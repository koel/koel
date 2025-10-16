<?php

namespace App\Services;

use App\Models\Theme;
use App\Models\User;
use App\Values\ImageWritingConfig;
use App\Values\Theme\ThemeCreateData;
use App\Values\Theme\ThemeProperties;

class ThemeService
{
    public function __construct(private readonly ImageStorage $imageStorage)
    {
    }

    public function createTheme(User $user, ThemeCreateData $data): Theme
    {
        $bgImage = '';
        $thumbnail = null;

        if ($data->bgImage) {
            // store the bg image and create a thumbnail too
            $bgImage = $this->imageStorage->storeImage(
                source: $data->bgImage,
                config: ImageWritingConfig::make(maxWidth: 2560),
            );

            $thumbnail = $this->imageStorage->storeImage(
                source: $data->bgImage,
                config: ImageWritingConfig::make(maxWidth: 640),
            );
        }

        return $user->themes()->create([
            'name' => $data->name,
            'thumbnail' => $thumbnail,
            'properties' => ThemeProperties::make(
                fgColor: $data->fgColor,
                bgColor: $data->bgColor,
                bgImage: $bgImage,
                highlightColor: $data->highlightColor,
                fontFamily: $data->fontFamily,
                fontSize: $data->fontSize,
            ),
        ]);
    }

    public function deleteTheme(Theme $theme): void
    {
        $theme->delete(); // will trigger cover/thumbnail cleanup in ThemeObserver
    }
}
