<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use App\Rules\HasAudioContentType;
use App\Rules\SafeUrl;
use Illuminate\Validation\Rule;

/**
 * @property string $id
 * @property string $streamUrl
 * @property string $name
 * @property ?string $homepageUrl
 */
class UpdateInternetRadioStationRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => [Rule::required(), Rule::string()],
            'streamUrl' => [Rule::bail(), Rule::required(), Rule::url(), new SafeUrl(), new HasAudioContentType()],
            'name' => [Rule::required(), Rule::string()],
            'homepageUrl' => [Rule::nullable(), Rule::string()],
        ];
    }
}
