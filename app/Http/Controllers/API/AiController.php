<?php

namespace App\Http\Controllers\API;

use App\Ai\Agents\KoelAssistant;
use App\Ai\AiAssistantResult;
use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\AiRequest;
use App\Http\Resources\PlaylistResource;
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
        $result = new AiAssistantResult();

        $agent = KoelAssistant::make(
            user: $user,
            result: $result,
            currentSongId: $request->input('current_song_id'),
            currentRadioStationId: $request->input('current_radio_station_id'),
        );

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
            ],
            'create_smart_playlist' => [
                'playlist' => PlaylistResource::make($result->data['playlist']),
            ],
            'play_radio_station', 'add_radio_station' => [
                'station' => RadioStationResource::make($result->data['station']),
            ],
            default => [],
        };
    }
}
