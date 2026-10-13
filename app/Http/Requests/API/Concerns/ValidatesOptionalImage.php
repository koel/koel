<?php

namespace App\Http\Requests\API\Concerns;

use App\Rules\ValidImageData;
use Illuminate\Contracts\Validation\ValidationRule;

trait ValidatesOptionalImage
{
    /** @return array<string|ValidationRule> */
    private static function optionalImageRules(): array
    {
        return ['string', 'sometimes', 'nullable', new ValidImageData()];
    }

    /**
     * Null when the field is absent, meaning the image stays unchanged; an empty string removes the image.
     */
    private function optionalImageInput(string $key): ?string
    {
        return $this->has($key) ? $this->string($key)->toString() : null;
    }
}
