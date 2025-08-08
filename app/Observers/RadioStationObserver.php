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
        if ($radioStation->isDirty('logo')) {
            // If the logo is being updated, delete the old logo file
            $oldLogo = $radioStation->getRawOriginal('logo');

            if ($oldLogo) {
                File::delete(radio_station_logo_path($oldLogo));
            }
        }
    }

    public function updated(RadioStation $radioStation): void
    {
    }

    public function deleted(RadioStation $radioStation): void
    {
        $logoPath = $radioStation->logo_path;

        if ($logoPath) {
            File::delete($logoPath);
        }
    }
}
