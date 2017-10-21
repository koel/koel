<?php

namespace App\Http\Requests\API;

/**
 * @property string media_path
 */
class SettingRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'media_path' => 'string|required|path.valid',
        ];
    }
}
