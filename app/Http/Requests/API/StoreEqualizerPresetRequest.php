<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read float $preamp
 * @property-read array<int, float> $gains
 */
class StoreEqualizerPresetRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string()],
            'preamp' => [Rule::required(), Rule::numeric()->between(-20, 20)],
            'gains' => [Rule::required(), Rule::array(), 'size:10'],
            'gains.*' => [Rule::required(), Rule::numeric()->between(-20, 20)],
        ];
    }
}
