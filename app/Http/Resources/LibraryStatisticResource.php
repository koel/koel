<?php

namespace App\Http\Resources;

use App\Values\Statistics\LibraryStatistic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryStatisticResource extends JsonResource
{
    public const array JSON_STRUCTURE = ['key', 'label', 'value', 'unit', 'used', 'limit'];

    public function __construct(
        private readonly LibraryStatistic $statistic,
    ) {
        parent::__construct($statistic);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->statistic->key,
            'label' => $this->statistic->label,
            'value' => $this->statistic->value,
            'unit' => $this->statistic->unit->value,
            'used' => $this->statistic->used,
            'limit' => $this->statistic->limit,
        ];
    }
}
