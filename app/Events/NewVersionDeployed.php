<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NewVersionDeployed implements ShouldBroadcastNow
{
    public function __construct(
        public readonly string $build,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('koel');
    }

    public function broadcastAs(): string
    {
        return 'app.updated';
    }

    /** @return array{build: string} */
    public function broadcastWith(): array
    {
        return ['build' => $this->build];
    }
}
