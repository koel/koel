<?php

namespace App\Http\Requests\API;

use App\Enums\LosslessCodec;
use Illuminate\Validation\Rule;

/**
 * @property-read bool|string|null $transcode
 */
class FetchSongPlayUrlRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'transcode' => [Rule::sometimes(), Rule::boolean()],
            'lossless' => [Rule::sometimes(), Rule::array()],
            'lossless.*' => [Rule::enum(LosslessCodec::class)],
        ];
    }
}
