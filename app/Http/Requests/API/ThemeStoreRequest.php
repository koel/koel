<?php

namespace App\Http\Requests\API;

use App\Rules\ValidImageData;
use App\Values\Theme\ThemeCreateData;
use HTMLPurifier;
use Illuminate\Validation\Rule;

class ThemeStoreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string()->max(191)],
            'fg_color' => [Rule::string(), Rule::sometimes(), Rule::nullable()],
            'bg_color' => [Rule::string(), Rule::sometimes(), Rule::nullable()],
            'font_family' => [Rule::string(), Rule::sometimes(), Rule::nullable()],
            'font_size' => [Rule::numeric(), Rule::sometimes(), Rule::nullable()],
            'bg_image' => [Rule::string(), Rule::sometimes(), Rule::nullable(), new ValidImageData()],
            'highlight_color' => [Rule::string(), Rule::sometimes(), Rule::nullable()],
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
