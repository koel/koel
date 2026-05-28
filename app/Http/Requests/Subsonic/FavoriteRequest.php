<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

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
            'id' => (array) $this->input('id', []),
            'albumId' => (array) $this->input('albumId', []),
            'artistId' => (array) $this->input('artistId', []),
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
