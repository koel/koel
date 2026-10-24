<?php

namespace App\Http\Requests\Subsonic;

use Illuminate\Validation\Rule;

class StreamRequest extends IdRequest
{
    /** @inheritdoc */
    public function rules(): array
    {
        return parent::rules()
        + [
            'maxBitRate' => [Rule::numeric()->integer()->min(0)],
            'timeOffset' => [Rule::numeric()->min(0)],
            'format' => [Rule::string()],
        ];
    }
}
