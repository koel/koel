<?php

namespace App\Http\Requests\API;

use App\Rules\ImageData;

abstract class AbstractMediaImageUpdateRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            $this->getImageFieldName() => ['string', 'required', new ImageData()],
        ];
    }

    public function getFileContentAsBinaryString(): string
    {
        [, $data] = explode(',', $this->{$this->getImageFieldName()});

        return base64_decode($data, true);
    }

    public function getFileExtension(): string
    {
        [$type,] = explode(';', $this->{$this->getImageFieldName()});
        [, $extension] = explode('/', $type);

        return $extension;
    }

    abstract protected function getImageFieldName(): string;
}
