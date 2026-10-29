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
use App\Services\Embeddables\EmbeddableResolverRegistry;
use App\Services\EmbedService;
use App\Values\EmbedOptions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmbedController extends Controller
{
    /** @param ?User $user */
    public function __construct(
        private readonly EmbedService $service,
        private readonly EmbeddableResolverRegistry $resolverRegistry,
        private readonly ThemeRepository $themeRepository,
        private readonly ?Authenticatable $user,
    ) {}

    public function resolveForEmbeddable(ResolveEmbedRequest $request)
    {
        $type = $request->enum('embeddable_type', EmbeddableType::class);
        $resolver = $this->resolverRegistry->getResolverForType($type);
        $embeddable = $resolver->findEmbeddable($request->embeddable_id, $this->user);

        abort_unless((bool) $embeddable, Response::HTTP_NOT_FOUND);
        throw_unless($resolver->canEmbed($this->user, $embeddable), AuthorizationException::class);

        $embed = $this->service->resolveEmbed($type, $resolver->getEmbeddableId($embeddable), $this->user);

        return EmbedResource::make($embed, $resolver->makeEmbeddableResource($embeddable));
    }

    public function getPayload(Request $request, Embed $embed)
    {
        $resolver = $this->resolverRegistry->getResolverForType($embed->embeddable_type);
        $embeddable = $resolver->findEmbeddable($embed->embeddable_id, $embed->user);

        abort_unless($embeddable && $resolver->canEmbed($embed->user, $embeddable), Response::HTTP_NOT_FOUND);

        $options = EmbedOptions::fromRequest($request);
        $theme = License::isPlus() ? $this->themeRepository->findUserThemeById($options->theme, $embed->user) : null;

        return response()->json([
            'embed' => EmbedResource::make(
                $embed,
                $resolver->makeEmbeddableResource($embeddable),
                $resolver->getPlayables($embeddable, $embed->user),
            ),
            'options' => EmbedOptionsResource::make($options),
            'theme' => $theme ? ThemeResource::make($theme) : null,
        ]);
    }
}
