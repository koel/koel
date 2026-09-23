<?php

namespace App\Http\Requests\API\Upload;

use App\Http\Requests\Request;

/** @property string $file_name */
class PresignUploadRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'file_name' => ['required', 'string', 'max:255'],
        ];
    }
}
