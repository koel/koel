<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SettingRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\MediaScanner;
use Illuminate\Contracts\Auth\Authenticatable;

class SettingController extends Controller
{
    /** @param User $user */
    public function __construct(private MediaScanner $mediaSyncService, private ?Authenticatable $user)
    {
    }

    public function update(SettingRequest $request)
    {
        $this->authorize('admin', User::class);

        Setting::set('media_path', rtrim(trim($request->media_path), '/'));

        $this->mediaSyncService->scan($this->user, makePublic: true);

        return response()->noContent();
    }
}
