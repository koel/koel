<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SettingRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\MediaSyncService;

class SettingController extends Controller
{
    public function __construct(private MediaSyncService $mediaSyncService)
    {
    }

    public function update(SettingRequest $request)
    {
        $this->authorize('admin', User::class);

        Setting::set('media_path', rtrim(trim($request->media_path), '/'));
        $this->mediaSyncService->sync();

        return response()->noContent();
    }
}
