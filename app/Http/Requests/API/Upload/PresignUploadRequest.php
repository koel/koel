<?php

namespace App\Http\Requests\API\Upload;

use App\Http\Requests\Request;
use App\Rules\SupportedAudioFileName;
use Illuminate\Validation\Rule;
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
            'file_name' => [Rule::required(), Rule::string()->max(255), new SupportedAudioFileName()],
            'file_size' => [
                Rule::required(),
                Rule::numeric()->integer()->min(1)->max(UploadedFile::getMaxFilesize()),
            ],
        ];
    }
}
