<?php

namespace App\Http\Requests\API;

use App\Rules\ValidImageData;

abstract class MediaImageUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            $this->getImageFieldName() => ['string', 'required', new ValidImageData()],
        ];
    }

    public function getFileContent(): string
    {
        return $this->{$this->getImageFieldName()};
    }

    abstract protected function getImageFieldName(): string;
}
