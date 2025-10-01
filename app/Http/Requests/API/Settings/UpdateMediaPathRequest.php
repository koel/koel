<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Rules\MediaPath;

/**
 * @property-read string $path
 */
class UpdateMediaPathRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'path' => ['string', new MediaPath()],
        ];
    }
}
