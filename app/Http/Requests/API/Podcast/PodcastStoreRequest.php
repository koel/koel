<?php

namespace App\Http\Requests\API\Podcast;

use App\Http\Requests\API\Request;
use App\Rules\SafeUrl;

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
            'url' => ['required', 'url', new SafeUrl()],
        ];
    }
}
