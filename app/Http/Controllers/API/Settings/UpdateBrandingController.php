<?php

namespace App\Http\Controllers\API\Settings;

use App\Attributes\RequiresPlus;
use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Settings\UpdateBrandingRequest;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

#[RequiresPlus]
class UpdateBrandingController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {
    }

    public function __invoke(UpdateBrandingRequest $request)
    {
        abort_unless(
            $this->user->hasPermissionTo(Permission::MANAGE_SETTINGS),
            Response::HTTP_FORBIDDEN,
        );

        $this->settingService->updateBranding(
            $request->name,
            $request->logo,
            $request->cover,
        );

        return response()->noContent();
    }
}
