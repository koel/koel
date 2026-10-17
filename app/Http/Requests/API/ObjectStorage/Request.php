<?php

namespace App\Http\Requests\API\ObjectStorage;

use App\Http\Requests\API\Request as BaseRequest;
use Illuminate\Validation\Rule;

class Request extends BaseRequest
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
