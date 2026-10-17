<?php

namespace App\Http\Requests\API;

use App\Rules\AllPlayablesAreAccessibleBy;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $songs
 */
class UpdateQueueStateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => [Rule::present(), Rule::array(), new AllPlayablesAreAccessibleBy($this->user())],
        ];
    }
}
