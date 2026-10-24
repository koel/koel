<?php

namespace App\Http\Requests;

use App\Enums\LosslessCodec;
use Illuminate\Validation\Rule;

/**
 * @property-read float|string $time
 * @property-read string $api_token
 */
class SongPlayRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'lossless' => [Rule::sometimes(), Rule::array()],
            'lossless.*' => [Rule::enum(LosslessCodec::class)],
        ];
    }
}
