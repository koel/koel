<?php

namespace App\Services\Embed;

use App\Enums\EmbeddableType;
use App\Models\Embed;
use App\Models\User;

class EmbedService
{
    public function resolveEmbed(EmbeddableType $type, ?string $embeddableId, User $user): Embed
    {
        return Embed::query()->firstOrCreate([
            'embeddable_type' => $type,
            'embeddable_id' => $embeddableId,
            'user_id' => $user->id,
        ]);
    }
}
