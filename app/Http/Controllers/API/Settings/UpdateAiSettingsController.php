<?php

namespace App\Http\Controllers\API\Settings;

use App\Attributes\RequiresPlus;
use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Settings\UpdateAiSettingsRequest;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;
use Laravel\Ai\Enums\Lab;

#[RequiresPlus]
class UpdateAiSettingsController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {}

    public function __invoke(UpdateAiSettingsRequest $request)
    {
        abort_unless($this->user->hasPermissionTo(Permission::MANAGE_SETTINGS), Response::HTTP_FORBIDDEN);

        $this->settingService->updateAiSettings(
            $this->user->organization,
            (bool) $request->enabled,
            Lab::from($request->provider),
            $request->api_key ?: null,
        );

        return response()->json(
            $this->settingService->getAiSettings($this->user->organization)->toArrayWithoutApiKey(),
        );
    }
}
