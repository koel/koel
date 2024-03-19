<?php

namespace App\Http\Requests\API;

use App\Rules\ImageData;

abstract class MediaImageUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            $this->getImageFieldName() => ['string', 'required', new ImageData()],
        ];
    }

    public function getFileContent(): string
    {
        return $this->{$this->getImageFieldName()};
    }

    abstract protected function getImageFieldName(): string;
}
