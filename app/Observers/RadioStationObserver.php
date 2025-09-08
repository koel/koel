<?php

namespace App\Observers;

use App\Models\RadioStation;
use Illuminate\Support\Facades\File;

class RadioStationObserver
{
    public function created(RadioStation $radioStation): void
    {
    }

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

    public function updated(RadioStation $radioStation): void
    {
    }

    public function deleted(RadioStation $radioStation): void
    {
        rescue_if($radioStation->logo_path, static fn (string $path) => File::delete($path));
    }
}
