<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

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
            'id' => [Rule::required(), Rule::string()],
            'rating' => [Rule::required(), Rule::numeric()->integer()->between(0, 5)],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge(['rating' => (int) $this->input('rating')]);
    }
}
