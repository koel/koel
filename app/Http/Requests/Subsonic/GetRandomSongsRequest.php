<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class GetRandomSongsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'size' => [Rule::numeric()->integer()->min(1)->max(500)],
        ];
    }
}
