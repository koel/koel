<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SettingRequest;
use App\Models\Setting;
use App\Services\MediaSyncService;

class SettingController extends Controller
{
    private MediaSyncService $mediaSyncService;

    public function __construct(MediaSyncService $mediaSyncService)
    {
        $this->mediaSyncService = $mediaSyncService;
    }

    public function update(SettingRequest $request)
    {
        Setting::set('media_path', rtrim(trim($request->media_path), '/'));

        // In a next version we should opt for a "MediaPathChanged" event,
        // but let's just do this async now.
        $this->mediaSyncService->sync();

        return response()->noContent();
    }
}
