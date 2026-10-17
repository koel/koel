<?php

namespace App\Http\Requests\API;

use App\Rules\ValidImageData;
use Illuminate\Validation\Rule;

abstract class MediaImageUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            $this->getImageFieldName() => [Rule::string(), Rule::required(), new ValidImageData()],
        ];
    }

    public function getFileContent(): string
    {
        return $this->{$this->getImageFieldName()};
    }

    abstract protected function getImageFieldName(): string;
}
