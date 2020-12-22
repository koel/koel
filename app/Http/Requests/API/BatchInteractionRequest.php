<?php

namespace App\Http\Requests\API;

/**
 * @property array<string> $songs
 */
class BatchInteractionRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|array',
        ];
    }
}
