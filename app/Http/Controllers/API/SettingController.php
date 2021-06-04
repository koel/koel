<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SettingRequest;
use App\Models\Setting;
use App\Services\MediaSyncService;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    private MediaSyncService $mediaSyncService;

    public function __construct(MediaSyncService $mediaSyncService)
    {
        $this->mediaSyncService = $mediaSyncService;
    }

    // @TODO: This should be a PUT request
    public function store(SettingRequest $request)
    {
        Setting::set('media_path', rtrim(trim($request->media_path), '/'));

        // In a next version we should opt for a "MediaPathChanged" event,
        // but let's just do this async now.
        $this->mediaSyncService->sync();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
