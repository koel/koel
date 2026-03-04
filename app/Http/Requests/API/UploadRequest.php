<?php

namespace App\Http\Requests\API;

use App\Http\Requests\Request;
use App\Rules\SupportedAudioFile;
use Illuminate\Http\UploadedFile;

/** @property UploadedFile $file */
class UploadRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', new SupportedAudioFile()],
        ];
    }
}
