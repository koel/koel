<?php

namespace App\Http\Requests\API\Download;

/**
 * @property array songs
 */
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
