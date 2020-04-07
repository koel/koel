<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SettingRequest;
use App\Models\Setting;
use App\Services\MediaSyncService;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * @group 8. Settings
 */
class SettingController extends Controller
{
    private $mediaSyncService;

    public function __construct(MediaSyncService $mediaSyncService)
    {
        $this->mediaSyncService = $mediaSyncService;
    }

    /**
     * Save the application settings
     *
     * Save the application settings. Right now there's only one setting to be saved (`media_path`).
     *
     * @bodyParam media_path string required Absolute path to the media folder. Example: /var/www/media/
     *
     * @response []
     *
     * @throws Exception
     *
     * @return JsonResponse
     */
    public function store(SettingRequest $request)
    {
        Setting::set('media_path', rtrim(trim($request->media_path), '/'));

        // In a next version we should opt for a "MediaPathChanged" event,
        // but let's just do this async now.
        $this->mediaSyncService->sync();

        return response()->json();
    }
}
