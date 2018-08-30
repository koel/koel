<?php

namespace App\Http\Requests\API\Download;

use App\Http\Requests\API\Request as BaseRequest;

class Request extends BaseRequest
{
    public function authorize(): bool
    {
        return config('koel.download.allow');
    }
}
