<?php

namespace App\Http\Requests\API;

/**
 * @property string[] $songs
 */
class BatchInteractionRequest extends Request
{
    public function rules(): array
    {
        return [
            'songs' => 'required|array',
        ];
    }
}
