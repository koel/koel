<?php

namespace App\Http\Requests\API\ObjectStorage\S3;

use App\Http\Requests\API\ObjectStorage\S3\Request as BaseRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $bucket
 * @property array<string> $tags
 * @property string $key
 */
class PutSongRequest extends BaseRequest
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'bucket' => [Rule::required()],
            'key' => [Rule::required()],
            'tags.duration' => [Rule::required(), Rule::numeric()],
        ];
    }
}
