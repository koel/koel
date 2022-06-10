<?php

namespace App\Http\Requests\API;

use App\Http\Requests\AbstractRequest;
use Illuminate\Http\UploadedFile;

/** @property UploadedFile $file */
class UploadRequest extends AbstractRequest
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:mp3,mpga,aac,flac,ogg,oga,opus',
            ],
        ];
    }
}
