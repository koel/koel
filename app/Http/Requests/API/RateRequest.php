<?php

namespace App\Http\Requests\API;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property int $rating
 */
class RateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'rating' => [Rule::required(), Rule::numeric()->integer()->min(0)->max(5)],
        ];
    }
}
