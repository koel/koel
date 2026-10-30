<?php

namespace App\Http\Controllers\API\Embed;

use App\Enums\EmbeddableType;
use App\Facades\License;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Embed\ResolveEmbedRequest;
use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Http\Resources\ThemeResource;
use App\Models\Embed;
use App\Models\User;
use App\Repositories\ThemeRepository;
use App\Services\Embed\EmbedService;
use App\Values\EmbedOptions;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmbedController extends Controller
{
    /** @param ?User $user */
    public function __construct(
        private readonly EmbedService $service,
        private readonly ThemeRepository $themeRepository,
        private readonly ?Authenticatable $user,
        private readonly Gate $gate,
    ) {}

    public function resolveForEmbeddable(ResolveEmbedRequest $request)
    {
        $type = $request->enum('embeddable_type', EmbeddableType::class);
        $embeddable = $this->service->findEmbeddable($type, $request->embeddable_id, $this->user);

        $this->authorize('embed', $embeddable);

        return EmbedResource::make($this->service->resolveEmbed($type, $embeddable, $this->user), $embeddable);
    }

    public function getPayload(Request $request, Embed $embed)
    {
        $embeddable = $this->service->findEmbeddable($embed->embeddable_type, $embed->embeddable_id, $embed->user);

        abort_if($this->gate->forUser($embed->user)->denies('embed', $embeddable), Response::HTTP_NOT_FOUND);

        $options = EmbedOptions::fromRequest($request);
        $theme = License::isPlus() ? $this->themeRepository->findUserThemeById($options->theme, $embed->user) : null;

        return response()->json([
            'embed' => EmbedResource::make($embed, $embeddable, $this->service->getPlayables($embed, $embeddable)),
            'options' => EmbedOptionsResource::make($options),
            'theme' => $theme ? ThemeResource::make($theme) : null,
        ]);
    }
}
