<?php

namespace App\Http\Requests\Subsonic;

class StreamRequest extends IdRequest
{
    /** @inheritdoc */
    public function rules(): array
    {
        return parent::rules()
        + [
            'maxBitRate' => ['integer', 'min:0'],
            'timeOffset' => ['numeric', 'min:0'],
        ];
    }
}
