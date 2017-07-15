<?php

namespace App\Http\Requests\API\ObjectStorage\GCS;

use App\Http\Requests\API\ObjectStorage\GCS\Request as BaseRequest;

/**
 * @property string bucket
 * @property string key
 */

class RemoveSongRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'bucket' => 'required',
            'key' => 'required',
        ];
    }
}
