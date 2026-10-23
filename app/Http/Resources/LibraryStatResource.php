<?php

namespace App\Http\Resources;

use App\Values\Statistics\LibraryStat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryStatResource extends JsonResource
{
    public const array JSON_STRUCTURE = ['key', 'label', 'value', 'unit', 'used', 'limit'];

    public function __construct(
        private readonly LibraryStat $stat,
    ) {
        parent::__construct($stat);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->stat->key,
            'label' => $this->stat->label,
            'value' => $this->stat->value,
            'unit' => $this->stat->unit->value,
            'used' => $this->stat->used,
            'limit' => $this->stat->limit,
        ];
    }
}
