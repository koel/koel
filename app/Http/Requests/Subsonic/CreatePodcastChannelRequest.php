<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use App\Rules\SafeUrl;
use Illuminate\Validation\Rule;

/**
 * @property string $url
 */
class CreatePodcastChannelRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'url' => [Rule::required(), Rule::string(), Rule::url(), new SafeUrl()],
        ];
    }
}
