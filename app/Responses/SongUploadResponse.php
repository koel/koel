<?php

namespace App\Responses;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Song;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

class SongUploadResponse extends BroadcastableResponse
{
    protected function __construct(private readonly Song $song, private readonly Album $album)
    {
    }

    public function broadcastOn(): Channel|string
    {
        return new PrivateChannel("user.{$this->song->owner->public_id}");
    }

    public function broadcastAs(): string
    {
        return 'song.uploaded';
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'song' => SongResource::make($this->song)->for($this->song->owner),
            'album' => AlbumResource::make($this->album)->for($this->album->user),
        ];
    }
}
