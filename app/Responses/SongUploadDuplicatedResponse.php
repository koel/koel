<?php

namespace App\Responses;

use App\Http\Resources\DuplicateUploadResource;
use App\Models\DuplicateUpload;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

class SongUploadDuplicatedResponse extends BroadcastableResponse
{
    protected function __construct(
        private readonly User $uploader,
        private readonly string $uploadKey,
        private readonly DuplicateUpload $duplicateUpload,
    ) {}

    public function broadcastOn(): Channel|string
    {
        return new PrivateChannel("user.{$this->uploader->public_id}");
    }

    public function broadcastAs(): string
    {
        return 'song.upload_duplicated';
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'upload_key' => $this->uploadKey,
            'duplicate' => new DuplicateUploadResource($this->duplicateUpload),
        ];
    }
}
