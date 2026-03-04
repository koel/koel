<?php

namespace App\Http\Requests\API\Podcast;

use App\Http\Requests\API\Request;

/**
 * @property-read string $url
 */
class PodcastStoreRequest extends Request
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => 'required|url',
        ];
    }
}
