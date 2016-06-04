<?php

namespace App\Http\Requests\API\Download;

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
        return env('ALLOW_DOWNLOAD', true);
    }

    public function rules() {
        return [];
    }
}
