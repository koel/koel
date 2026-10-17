<?php

namespace App\Http\Requests\API\ObjectStorage\S3;

use App\Http\Requests\API\ObjectStorage\S3\Request as BaseRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $bucket
 * @property string $key
 */
class RemoveSongRequest extends BaseRequest
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'bucket' => [Rule::required()],
            'key' => [Rule::required()],
        ];
    }
}
