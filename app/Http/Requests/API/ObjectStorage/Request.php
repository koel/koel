<?php

namespace App\Http\Requests\API\ObjectStorage;

use App\Http\Requests\API\Request as BaseRequest;

class Request extends BaseRequest
{
    public function rules(): array
    {
        return [
            'bucket' => 'required',
            'key' => 'required',
        ];
    }
}
