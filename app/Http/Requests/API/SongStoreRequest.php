<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SongStoreRequest extends FormRequest
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
            'file' => 'required|file|mimes:mp3,ogg,m4a,aac,flac',
        ];
    }
}
