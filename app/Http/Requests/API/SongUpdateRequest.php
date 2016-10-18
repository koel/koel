<?php

namespace App\Http\Requests\API;

/**
 * @property array songs
 * @property array data
 */
class SongUpdateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data' => 'required|array',
            'songs' => 'required|array',
        ];
    }
}
