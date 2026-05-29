<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

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
            'id' => ['required', 'string'],
            'streamUrl' => ['required', 'string'],
            'name' => ['required', 'string'],
            'homepageUrl' => ['nullable', 'string'],
        ];
    }
}
