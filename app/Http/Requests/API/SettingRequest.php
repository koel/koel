<?php

namespace App\Http\Requests\API;

use App\Rules\MediaPath;

/**
 * @property-read string $media_path
 */
class SettingRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'media_path' => ['string', new MediaPath()],
        ];
    }
}
