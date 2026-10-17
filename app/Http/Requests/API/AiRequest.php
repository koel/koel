<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

class AiRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'prompt' => [Rule::required(), Rule::string()->max(500)],
            'current_song_id' => [Rule::nullable(), Rule::string()],
            'current_radio_station_id' => [Rule::nullable(), Rule::string()],
            'conversation_id' => [Rule::nullable(), Rule::string()],
        ];
    }
}
