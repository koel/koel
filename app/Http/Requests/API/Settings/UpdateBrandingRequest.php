<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use Closure;
use Illuminate\Support\Facades\URL;

/**
 * @property-read string $name
 * @property-read ?string $logo
 * @property-read ?string $cover
 */
class UpdateBrandingRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        $validImageDataOrUrl = static function (string $attribute, mixed $value, Closure $fail): void {
            if (URL::isValidUrl($value)) {
                return;
            }

            (new ValidImageData())->validate($attribute, $value, $fail);
        };

        return [
            'name' => 'required|string',
            'logo' => ['sometimes', 'nullable', $validImageDataOrUrl],
            'cover' => ['sometimes', 'nullable', $validImageDataOrUrl],
        ];
    }
}
