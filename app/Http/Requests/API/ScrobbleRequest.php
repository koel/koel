<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property int $timestamp
 */
class ScrobbleRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return ['timestamp' => [Rule::required(), Rule::numeric()]];
    }
}
