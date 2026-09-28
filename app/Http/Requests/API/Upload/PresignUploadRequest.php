<?php

namespace App\Http\Requests\API\Upload;

use App\Http\Requests\Request;
use App\Rules\SupportedAudioFileName;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @property string $file_name
 * @property int $file_size
 */
class PresignUploadRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'file_name' => ['required', 'string', 'max:255', new SupportedAudioFileName()],
            'file_size' => ['required', 'integer', 'min:1', 'max:' . UploadedFile::getMaxFilesize()],
        ];
    }
}
