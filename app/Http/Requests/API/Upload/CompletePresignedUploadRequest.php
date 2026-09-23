<?php

namespace App\Http\Requests\API\Upload;

use App\Http\Requests\Request;

/** @property string $key */
class CompletePresignedUploadRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string'],
        ];
    }
}
