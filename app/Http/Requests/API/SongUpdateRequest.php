<?php

namespace App\Http\Requests\API;

use App\Facades\License;
use App\Models\Song;

/**
 * @property array<string> $songs
 * @property array<mixed> $data
 */
class SongUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'songs' => 'required|array|exists:songs,id',
        ];
    }

    public function authorize(): bool
    {
        if (License::isCommunity()) {
            return $this->user()->is_admin;
        }

        return Song::query()
            ->whereIn('id', $this->songs)
            ->get()
            ->every(fn (Song $song): bool => $song->owner_id === $this->user()->id);
    }
}
