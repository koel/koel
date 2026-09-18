<?php

namespace App\Observers;

use App\Models\RadioStation;
use App\Services\Image\ModelImageObserver;

class RadioStationObserver
{
    private ModelImageObserver $logoObserver;

    public function __construct()
    {
        $this->logoObserver = ModelImageObserver::make('logo');
    }

    public function updating(RadioStation $radioStation): void
    {
        $this->logoObserver->onModelUpdating($radioStation);
    }

    public function deleted(RadioStation $radioStation): void
    {
        $this->logoObserver->onModelDeleted($radioStation);
    }
}
