<?php

namespace App\Http\Controllers\API;

use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ThemeStoreRequest;
use App\Http\Resources\ThemeResource;
use App\Models\Theme;
use App\Models\User;
use App\Repositories\ThemeRepository;
use App\Services\ThemeService;
use Illuminate\Contracts\Auth\Authenticatable;

#[RequiresPlus]
class ThemeController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly ThemeService $themeService,
        private readonly ThemeRepository $themeRepository,
        private readonly Authenticatable $user,
    ) {
    }

    public function index()
    {
        return ThemeResource::collection($this->themeRepository->getAllByUser($this->user));
    }

    public function store(ThemeStoreRequest $request)
    {
        return ThemeResource::make($this->themeService->createTheme($this->user, $request->toDto()));
    }

    public function destroy(Theme $theme)
    {
        $this->authorize('own', $theme);
        $this->themeService->deleteTheme($theme);

        return response()->noContent();
    }
}
