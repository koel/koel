<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class Search3Request extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'query' => [Rule::nullable(), Rule::string()],
            'artistCount' => [Rule::numeric()->integer()->min(0)->max(500)],
            'artistOffset' => [Rule::numeric()->integer()->min(0)],
            'albumCount' => [Rule::numeric()->integer()->min(0)->max(500)],
            'albumOffset' => [Rule::numeric()->integer()->min(0)],
            'songCount' => [Rule::numeric()->integer()->min(0)->max(500)],
            'songOffset' => [Rule::numeric()->integer()->min(0)],
        ];
    }
}
