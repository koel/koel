<?php

namespace App\Http\Controllers\API;

use App\Ai\Agents\KoelAssistant;
use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Attributes\RequiresPlus;
use App\Enums\FavoriteableType;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\AiRequest;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\PodcastResource;
use App\Http\Resources\RadioStationResource;
use App\Http\Resources\SongResource;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Laravel\Ai\Exceptions\AiException;

#[RequiresPlus]
class AiController extends Controller
{
    /** @param User $user */
    public function __invoke(AiRequest $request, Authenticatable $user): JsonResponse
    {
        // Bind as a singleton so the same instance is shared between the controller and the tools
        // resolved by the container. This allows tools to write to $result during execution,
        // and the controller to read the outcome afterward.
        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);

        app()->instance(
            AiRequestContext::class,
            new AiRequestContext(
                user: $user,
                currentSongId: $request->input('current_song_id'),
                currentRadioStationId: $request->input('current_radio_station_id'),
            ),
        );

        $agent = KoelAssistant::make();

        $conversationId = $request->input('conversation_id');

        if ($conversationId) {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        try {
            $response = $agent->prompt($request->input('prompt'));
        } catch (AiException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'action' => null,
                'data' => [],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => (string) $response,
            'action' => $result->action,
            'data' => self::serializeResultData($result),
            'conversation_id' => $response->conversationId,
        ]);
    }

    private static function serializeResultData(AiAssistantResult $result): array
    {
        return match ($result->action) {
            'play_songs' => [
                'songs' => SongResource::collection($result->data['songs']),
                'queue' => $result->data['queue'] ?? false,
            ],
            'add_to_favorites', 'remove_from_favorites' => self::serializeFavoriteData($result),
            'add_to_playlist', 'remove_from_playlist' => [
                'songs' => SongResource::collection($result->data['songs']),
                'playlist' => PlaylistResource::make($result->data['playlist']),
            ],
            'create_smart_playlist' => [
                'playlist' => PlaylistResource::make($result->data['playlist']),
            ],
            'play_radio_station', 'add_radio_station' => [
                'station' => RadioStationResource::make($result->data['station']),
            ],
            'show_lyrics' => [
                'lyrics' => $result->data['lyrics'],
            ],
            default => [],
        };
    }

    private static function serializeFavoriteData(AiAssistantResult $result): array
    {
        /** @var FavoriteableType $type */
        $type = $result->data['type'];
        $entities = $result->data['entities'];

        $serialized = match ($type) {
            FavoriteableType::ALBUM => ['albums' => AlbumResource::collection($entities)],
            FavoriteableType::ARTIST => ['artists' => ArtistResource::collection($entities)],
            FavoriteableType::RADIO_STATION => ['stations' => RadioStationResource::collection($entities)],
            FavoriteableType::PODCAST => ['podcasts' => PodcastResource::collection($entities)],
            default => ['songs' => SongResource::collection($entities)],
        };

        return ['type' => $type->value, ...$serialized];
    }
}
