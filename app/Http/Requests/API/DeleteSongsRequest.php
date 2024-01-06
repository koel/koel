<?php

namespace App\Http\Requests\API;

use App\Facades\License;
use App\Models\Song;

/** @property-read array<string> $songs */
class DeleteSongsRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
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
