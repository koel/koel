<?php

namespace App\Http\Requests\API\Upload;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/** @property string $key */
class CompletePresignedUploadRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'key' => [Rule::required(), Rule::string()],
        ];
    }
}
