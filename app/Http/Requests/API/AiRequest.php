<?php

namespace App\Http\Requests\API;

class AiRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:500'],
            'current_song_id' => ['nullable', 'string'],
            'current_radio_station_id' => ['nullable', 'string'],
            'conversation_id' => ['nullable', 'string'],
        ];
    }
}
