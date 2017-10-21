<?php

namespace App\Http\Requests\API\ObjectStorage\S3;

use App\Http\Requests\API\ObjectStorage\S3\Request as BaseRequest;

/**
 * @property string bucket
 * @property array  tags
 * @property string key
 */
class PutSongRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'bucket' => 'required',
            'key' => 'required',
            'tags.duration' => 'required|numeric',
        ];
    }
}
