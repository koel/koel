<?php

namespace App\Http\Requests\API;

/**
 * @property-read array<string> $songs
 */
class UpdateQueueStateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        // @todo validate song/episode ids
        return [
            'songs' => ['array'],
        ];
    }
}
