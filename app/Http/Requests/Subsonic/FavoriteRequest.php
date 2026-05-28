<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Support\Arr;

/**
 * @property list<string> $id
 * @property list<string> $albumId
 * @property list<string> $artistId
 */
class FavoriteRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => Arr::wrap($this->input('id')),
            'albumId' => Arr::wrap($this->input('albumId')),
            'artistId' => Arr::wrap($this->input('artistId')),
        ]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['array'],
            'id.*' => ['string'],
            'albumId' => ['array'],
            'albumId.*' => ['string'],
            'artistId' => ['array'],
            'artistId.*' => ['string'],
        ];
    }
}
