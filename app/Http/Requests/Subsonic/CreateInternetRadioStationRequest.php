<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use App\Rules\HasAudioContentType;
use App\Rules\SafeUrl;
use Illuminate\Validation\Rule;

/**
 * @property string $streamUrl
 * @property string $name
 * @property ?string $homepageUrl
 */
class CreateInternetRadioStationRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'streamUrl' => [Rule::bail(), Rule::required(), Rule::url(), new SafeUrl(), new HasAudioContentType()],
            'name' => [Rule::required(), Rule::string()],
            'homepageUrl' => [Rule::nullable(), Rule::string()],
        ];
    }
}
