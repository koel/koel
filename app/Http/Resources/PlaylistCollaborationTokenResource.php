<?php

namespace App\Http\Resources;

use App\Models\PlaylistCollaborationToken;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistCollaborationTokenResource extends JsonResource
{
    public function __construct(private PlaylistCollaborationToken $token)
    {
        parent::__construct($token);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'playlist_collaboration_tokens',
            'token' => $this->token->token,
        ];
    }
}
