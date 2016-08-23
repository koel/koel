<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SettingRequest;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Save the application settings.
     *
     * @param SettingRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SettingRequest $request)
    {
        // For right now there's only one setting to be saved
        Setting::set('media_path', rtrim(trim($request->input('media_path')), '/'));

        // Changing the settings does not force scanning the library anymore
        return response()->json();
    }
}
