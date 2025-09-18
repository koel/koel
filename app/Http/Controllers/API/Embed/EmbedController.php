<?php

namespace App\Http\Controllers\API\Embed;

use App\Enums\EmbeddableType;
use App\Exceptions\EmbeddableNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Embed\ResolveEmbedRequest;
use App\Http\Resources\EmbedOptionsResource;
use App\Http\Resources\EmbedResource;
use App\Models\Embed;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\EmbedService;
use App\Values\EmbedOptions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmbedController extends Controller
{
    /** @param ?User $user */
    public function __construct(
        private readonly EmbedService $service,
        private readonly SongRepository $songRepository,
        private readonly ?Authenticatable $user,
    ) {
    }

    public function resolveForEmbeddable(ResolveEmbedRequest $request)
    {
        $type = $request->enum('embeddable_type', EmbeddableType::class);
        $embeddable = $type->modelClass()::findOrFail($request->embeddable_id);

        $this->authorize('access', $embeddable);

        return EmbedResource::make($this->service->resolveEmbedForEmbeddable($embeddable, $this->user));
    }

    public function getPayload(Request $request, Embed $embed)
    {
        try {
            return response()->json([
                'embed' => EmbedResource::make($embed, $this->songRepository->getForEmbed($embed)),
                'options' => EmbedOptionsResource::make(EmbedOptions::fromRequest($request)),
            ]);
        } catch (EmbeddableNotFoundException) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
