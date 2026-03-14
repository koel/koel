<?php

namespace App\Http\Controllers\API;

use App\Ai\Agents\KoelAssistant;
use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Serializers\AiResultSerializerRegistry;
use App\Attributes\RequiresPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\AiRequest;
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
            'data' => AiResultSerializerRegistry::serialize($result),
            'conversation_id' => $response->conversationId,
        ]);
    }
}
