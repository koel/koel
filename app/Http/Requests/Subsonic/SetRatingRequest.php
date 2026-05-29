<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $id
 * @property int $rating
 */
class SetRatingRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'rating' => ['required', 'integer', 'between:0,5'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge(['rating' => (int) $this->input('rating')]);
    }
}
