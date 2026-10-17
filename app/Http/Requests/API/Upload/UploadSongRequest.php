<?php

namespace App\Http\Requests\API\Upload;

use App\Http\Requests\Request;
use App\Rules\SupportedAudioFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/** @property UploadedFile $file */
class UploadSongRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'file' => [Rule::required(), Rule::file(), new SupportedAudioFile()],
        ];
    }
}
