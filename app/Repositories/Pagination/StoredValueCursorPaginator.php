<?php

namespace App\Repositories\Pagination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Str;

/**
 * Builds cursors from the values stored in the database, not from cast attributes, so a cast that
 * stands in for an empty value (e.g. a song title falling back to its file name) can't move the
 * next page's starting point.
 */
class StoredValueCursorPaginator extends CursorPaginator
{
    /** @inheritdoc */
    public function getParametersForItem(mixed $item): array
    {
        if (!$item instanceof Model) {
            return parent::getParametersForItem($item);
        }

        return collect($this->parameters)
            ->filter()
            ->flip()
            ->map(
                fn (mixed $_, string $parameter) => (
                    $this->getPivotParameterForItem($item, $parameter) ?? $item->getRawOriginal(
                        $parameter,
                    ) ?? $item->getRawOriginal(Str::afterLast($parameter, '.'))
                ),
            )
            ->all();
    }
}
