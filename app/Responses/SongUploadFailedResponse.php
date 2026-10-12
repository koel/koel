<?php

namespace App\Responses;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

class SongUploadFailedResponse extends BroadcastableResponse
{
    protected function __construct(
        private readonly User $uploader,
        private readonly string $uploadKey,
        private readonly string $message,
    ) {}

    public function broadcastOn(): Channel|string
    {
        return new PrivateChannel("user.{$this->uploader->public_id}");
    }

    public function broadcastAs(): string
    {
        return 'song.upload_failed';
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'upload_key' => $this->uploadKey,
            'message' => $this->message,
        ];
    }
}
