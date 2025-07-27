<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Favorites\DestroyFavoritesRequest;
use App\Http\Requests\API\Favorites\StoreFavoritesRequest;
use App\Http\Requests\API\Favorites\ToggleFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Contracts\Favoriteable;
use App\Models\User;
use App\Services\FavoriteService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class FavoriteController extends Controller
{
    /** @param User $user */
    public function __construct(private readonly FavoriteService $service, private readonly Authenticatable $user)
    {
    }

    public function toggle(ToggleFavoriteRequest $request)
    {
        $modelType = Relation::getMorphedModel($request->type);

        /** @var Model|Favoriteable $entity */
        $entity = $modelType::findOrFail($request->id);

        $this->authorize('access', $entity);

        $favorite = $this->service->toggleFavorite($entity, $this->user);

        return $favorite ? FavoriteResource::make($favorite) : response()->noContent();
    }

    public function store(StoreFavoritesRequest $request)
    {
        $modelType = Relation::getMorphedModel($request->type);

        /** @var Collection<int, Favoriteable&Model> $entities */
        $entities = $modelType::query()
            ->whereIn('id', $request->ids)
            ->get();

        $entities->each(fn (Model $entity) => $this->authorize('access', $entity));

        $this->service->batchFavorite($entities, $this->user);

        return response()->noContent();
    }

    public function destroy(DestroyFavoritesRequest $request)
    {
        $modelType = Relation::getMorphedModel($request->type);

        /** @var Collection<int, Favoriteable&Model> $entities */
        $entities = $modelType::query()
            ->whereIn('id', $request->ids)
            ->get();

        $entities->each(fn (Model $entity) => $this->authorize('access', $entity));

        $this->service->batchUndoFavorite($entities, $this->user);

        return response()->noContent();
    }
}
