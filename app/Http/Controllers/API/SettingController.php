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
        $this->mediaSyncService->sync(Setting::get('media_path'));

        return response()->noContent();
    }
}
