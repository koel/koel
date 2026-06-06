<?php

namespace App\Http\Requests\API;

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
            'name' => ['required', 'string'],
            'preamp' => ['required', 'numeric', 'between:-20,20'],
            'gains' => ['required', 'array', 'size:10'],
            'gains.*' => ['required', 'numeric', 'between:-20,20'],
        ];
    }
}
