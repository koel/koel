<?php

namespace App\Http\Controllers\API;

use App\Facades\DropbeatMedia;

use App\Http\Requests\API\DropbeatRequest;
use Illuminate\Support\Facades\Log;
// use App\Models\Setting;

class DropbeatController extends Controller
{
    /**
     * Save the application settings.
     *
     * @param SettingRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(DropbeatRequest $request)
    {
        // For right now there's only one setting to be saved
        // Setting::set('media_path', rtrim(trim($request->input('media_path')), '/'));

        // In a next version we should opt for a "MediaPathChanged" event,
        // but let's just do this async now.

        DropbeatMedia::sync($request);
        //
        return response()->json();
    }
}
