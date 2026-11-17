<?php

namespace App\Http\Controllers\API\Settings;

use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Settings\UpdateYouTubeSettingsRequest;
use App\Models\User;
use App\Services\Integrations\YouTubeService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UpdateYouTubeSettingsController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly YouTubeService $youTubeService,
        private readonly Authenticatable $user,
    ) {}

    public function __invoke(UpdateYouTubeSettingsRequest $request)
    {
        abort_unless($this->user->hasPermissionTo(Permission::MANAGE_SETTINGS), Response::HTTP_FORBIDDEN);

        $this->youTubeService->updateApiKey($this->user->organization, $request->api_key ?: null);

        return response()->json([
            'has_api_key' => $this->youTubeService->hasStoredApiKey($this->user->organization),
            'enabled' => $this->youTubeService->enabled($this->user->organization),
        ]);
    }
}
