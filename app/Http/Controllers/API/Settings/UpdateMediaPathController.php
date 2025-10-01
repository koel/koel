<?php

namespace App\Http\Controllers\API\Settings;

use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Settings\UpdateMediaPathRequest;
use App\Models\User;
use App\Services\Scanners\DirectoryScanner;
use App\Services\SettingService;
use App\Values\Scanning\ScanConfiguration;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UpdateMediaPathController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly DirectoryScanner $mediaSyncService,
        private readonly Authenticatable $user
    ) {
    }

    public function __invoke(UpdateMediaPathRequest $request)
    {
        abort_unless(
            $this->user->hasPermissionTo(Permission::MANAGE_SETTINGS),
            Response::HTTP_FORBIDDEN,
        );

        $this->mediaSyncService->scan(
            directory: $this->settingService->updateMediaPath($request->path),
            config: ScanConfiguration::make(owner: $this->user, makePublic: true),
        );

        return response()->noContent();
    }
}
