<?php

namespace App\Responses;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

/**
 * A broadcastable response is a class that can be used to return a JSON response in a standard HTTP lifecycle
 * (i.e., from a controller) and can also be broadcasted to a channel in an asynchronous manner.
 * Since Koel supports both HTTP and WebSocket communication (via Pusher), this design ensures consistency
 * and simplifies the process of sending data to clients as well as for the client to handle the data.
 */
abstract class BroadcastableResponse implements Arrayable, ShouldBroadcast, ShouldDispatchAfterCommit
{
    /**
     * Transform the object into a JSON response, ready for returning from a controller.
     */
    public function toResponse(int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return response()->json(
            data: $this->toArray(),
            status: $status,
            headers: $headers,
            options: $options
        );
    }

    /**
     * @return array<mixed>
     */
    public function broadcastWith(): array
    {
        return $this->toArray();
    }

    abstract public function broadcastAs(): string;

    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
