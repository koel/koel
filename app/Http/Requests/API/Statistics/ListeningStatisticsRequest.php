<?php

namespace App\Http\Requests\API\Statistics;

use App\Enums\ListeningPeriod;
use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

class ListeningStatisticsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::enum(ListeningPeriod::class)],
        ];
    }

    public function period(): ListeningPeriod
    {
        return $this->enum('period', ListeningPeriod::class);
    }
}
