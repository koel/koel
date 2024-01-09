<?php

namespace App\Http\Requests\API;

use App\Facades\License;

/**
 * @property-read array<string> $songs
 */
class ChangeSongsVisibilityRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|exists:songs,id',
        ];
    }

    public function authorize(): bool
    {
        return License::isPlus();
    }
}
