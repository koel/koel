<?php

namespace App\Http\Requests\API\ObjectStorage\GCS;

use App\Http\Requests\API\ObjectStorage\GCS\Request as BaseRequest;

/**
 * @property string bucket
 * @property array  tags
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
