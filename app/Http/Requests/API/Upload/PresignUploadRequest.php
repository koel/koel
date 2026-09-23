<?php

namespace App\Http\Requests\API\Upload;

use App\Http\Requests\Request;
use App\Rules\SupportedAudioFileName;

/** @property string $file_name */
class PresignUploadRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'file_name' => ['required', 'string', 'max:255', new SupportedAudioFileName()],
        ];
    }
}
