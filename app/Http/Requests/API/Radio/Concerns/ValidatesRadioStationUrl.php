<?php

namespace App\Http\Requests\API\Radio\Concerns;

use App\Models\RadioStation;
use App\Rules\HasAudioContentType;
use App\Rules\SafeUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ValidatesRadioStationUrl
{
    /** @return array<string|Unique|ValidationRule> */
    private function radioStationUrlRules(?RadioStation $stationBeingUpdated = null): array
    {
        $userId = $this->user()->id;

        return [
            'bail',
            'required',
            'url',
            Rule::unique('radio_stations')
                ->where(static fn (Builder $query) => $query->where('user_id', $userId))
                ->when($stationBeingUpdated, static fn (Unique $rule) => $rule->ignore($stationBeingUpdated)),
            new SafeUrl(),
            new HasAudioContentType(),
        ];
    }
}
