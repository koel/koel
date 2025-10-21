<?php

namespace App\Observers;

use App\Models\RadioStation;
use Illuminate\Support\Facades\File;

class RadioStationObserver
{
    public function updating(RadioStation $radioStation): void
    {
        if (!$radioStation->isDirty('logo')) {
            return;
        }

        rescue_if(
            $radioStation->getRawOriginal('logo'),
            static function (string $oldLogo): void {
                File::delete(image_storage_path($oldLogo));
            }
        );
    }

    public function deleted(RadioStation $radioStation): void
    {
        rescue_if($radioStation->logo, static fn () => File::delete(image_storage_path($radioStation->logo)));
    }
}
