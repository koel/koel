<?php

namespace App\Http\Requests\Download;

use App\Http\Requests\API\Request as BaseRequest;

abstract class Request extends BaseRequest
{
    public function authorize(): bool
    {
        return config('koel.download.allow');
    }
}
