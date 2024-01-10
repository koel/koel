<?php

namespace App\Http\Requests\API;

use App\Rules\ImageData;
use Illuminate\Support\Str;

abstract class MediaImageUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            $this->getImageFieldName() => ['string', 'required', new ImageData()],
        ];
    }

    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function getFileContentAsBinaryString(): string
    {
        return base64_decode(Str::after($this->{$this->getImageFieldName()}, ','), true);
    }

    public function getFileExtension(): string
    {
        return Str::after(Str::before($this->{$this->getImageFieldName()}, ';'), '/');
    }

    abstract protected function getImageFieldName(): string;
}
