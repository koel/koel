<?php

namespace App\Http\Requests\API\Podcast;

use App\Http\Requests\API\Request;
use App\Rules\SafeUrl;
use Illuminate\Validation\Rule;

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
            'url' => [Rule::required(), Rule::url(), new SafeUrl()],
        ];
    }
}
