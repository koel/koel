<?php

namespace App\Http\Requests\API\ObjectStorage;

use App\Http\Requests\API\Request as BaseRequest;

class Request extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bucket' => 'required',
            'key' => 'required',
        ];
    }
}
