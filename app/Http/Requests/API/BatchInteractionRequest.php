<?php

namespace App\Http\Requests\API;

/**
 * @property array songs
 */
class BatchInteractionRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

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
