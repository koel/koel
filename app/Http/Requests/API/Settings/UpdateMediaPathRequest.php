<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Rules\MediaPath;
use Illuminate\Validation\Rule;

/**
 * @property-read string $path
 */
class UpdateMediaPathRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'path' => [Rule::string(), new MediaPath()],
        ];
    }
}
