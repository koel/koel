<?php

namespace App\Http\Requests\API;

class SyncRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     * Currently, only user with admin rights are allowed to do this.
     * When per-user media library will be available, this restriction will be removed.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request (currently none).
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
