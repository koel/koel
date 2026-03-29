<?php

namespace App\Http\Requests\API;

use App\Rules\AllPlayablesAreAccessibleBy;

/**
 * @property-read array<string> $songs
 */
class UpdateQueueStateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => ['present', 'array', new AllPlayablesAreAccessibleBy($this->user())],
        ];
    }
}
