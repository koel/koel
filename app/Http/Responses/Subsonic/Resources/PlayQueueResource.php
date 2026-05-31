<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Song;
use App\Models\User;
use App\Values\QueueState;

final class PlayQueueResource
{
    /** Keys always present after stripNulls. Nullable fields (current, position) are omitted when null. */
    public const array JSON_STRUCTURE = [
        'username',
        'changed',
        'changedBy',
    ];

    /**
     * @return array{
     *     username: string,
     *     changed: string,
     *     changedBy: string,
     *     current: ?string,
     *     position: ?int,
     *     entry?: list<array<string, mixed>>,
     * }
     */
    public static function toArray(QueueState $state, User $user): array
    {
        $payload = [
            'username' => $user->email,
            'changed' => $state->changedAt?->toIso8601String() ?? '',
            'changedBy' => $state->changedBy ?: 'koel',
            'current' => $state->currentPlayable?->id,
            'position' => $state->playbackPosition !== null ? $state->playbackPosition * 1000 : null,
        ];

        $entries = $state->playables->map(static fn (Song $song) => SongResource::toArray($song, $user))->all();

        if ($entries) {
            $payload['entry'] = $entries;
        }

        return $payload;
    }
}
