<?php

namespace App\Http\Controllers\API;

use App\Events\MediaPathChanged;
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
    public function save(SettingRequest $request)
    {
        // For right now there's only one setting to be saved
        Setting::set('media_path', rtrim(trim($request->input('media_path')), '/'));

        event(new MediaPathChanged(Setting::get('media_path')));

        return response()->json();
    }
}
