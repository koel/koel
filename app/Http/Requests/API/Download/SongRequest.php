<?php

namespace App\Http\Requests\API\Download;

class SongRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'songs' => 'required|array',
        ];
    }
}
