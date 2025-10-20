<?php

namespace App\Http\Requests\API;

use App\Rules\ValidImageData;
use App\Values\Theme\ThemeCreateData;
use HTMLPurifier;

class ThemeStoreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'fg_color' => 'string|sometimes|nullable',
            'bg_color' => 'string|sometimes|nullable',
            'font_family' => 'string|sometimes|nullable',
            'font_size' => 'numeric|sometimes|nullable',
            'bg_image' => ['string', 'sometimes', 'nullable', new ValidImageData()],
            'highlight_color' => 'string|sometimes|nullable',
        ];
    }

    public function toDto(): ThemeCreateData
    {
        $purifier = new HTMLPurifier();

        return ThemeCreateData::make(
            name: $this->input('name'),
            fgColor: $purifier->purify($this->string('fg_color')),
            bgColor: $purifier->purify($this->string('bg_color')),
            bgImage: $this->string('bg_image'),
            highlightColor: $purifier->purify($this->string('highlight_color')),
            fontFamily: $purifier->purify($this->string('font_family')),
            fontSize: $this->float('font_size'),
        );
    }
}
