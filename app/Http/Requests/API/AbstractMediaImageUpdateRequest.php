<?php

namespace App\Http\Requests\API;

use App\Rules\ImageData;

abstract class AbstractMediaImageUpdateRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            $this->getImageFieldName() => ['string', 'required', new ImageData()]
        ];
    }

    public function getFileContentAsBinaryString(): string
    {
        [$_, $data] = explode(',', $this->{$this->getImageFieldName()});

        return base64_decode($data);
    }

    public function getFileExtension(): string
    {
        [$type, $data] = explode(';', $this->{$this->getImageFieldName()});
        [$_, $extension] = explode('/', $type);

        return $extension;
    }

    abstract protected function getImageFieldName(): string;
}
