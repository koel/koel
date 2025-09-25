<?php

namespace App\Http\Controllers\API;

use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\SettingRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\Scanners\DirectoryScanner;
use App\Values\Scanning\ScanConfiguration;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly DirectoryScanner $mediaSyncService,
        private readonly Authenticatable $user
    ) {
    }

    public function update(SettingRequest $request)
    {
        abort_unless(
            $this->user->hasPermissionTo(Permission::MANAGE_SETTINGS),
            Response::HTTP_FORBIDDEN,
        );

        $path = rtrim(trim($request->media_path), DIRECTORY_SEPARATOR);

        Setting::set('media_path', $path);

        $this->mediaSyncService->scan($path, ScanConfiguration::make(owner: $this->user, makePublic: true));

        return response()->noContent();
    }
}
