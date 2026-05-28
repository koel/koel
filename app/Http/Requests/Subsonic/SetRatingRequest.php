<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $id
 * @property int $rating
 */
class SetRatingRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge(['rating' => (int) $this->input('rating', 0)]);
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'rating' => ['required', 'integer', 'between:0,5'],
        ];
    }
}
