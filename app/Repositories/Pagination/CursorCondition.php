<?php

namespace App\Repositories\Pagination;

use App\Values\Pagination\CursorSortKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Seeks past the cursor with a row-value comparison like `(title, id) > (?, ?)` whenever it's exact, so the database
 * can jump into an index instead of reading it from the start. Laravel's `cursorPaginate()` can't seek, and fails
 * when the cursor holds a NULL.
 */
final class CursorCondition
{
    /** @param Collection<int, CursorSortKey> $keys */
    public static function wherePastCursor(Builder $builder, Collection $keys): void
    {
        $canCompareRowValues =
            $keys->pluck('ascending')->unique()->count() === 1
            && $keys->every(static fn (CursorSortKey $key) => $key->value !== null && !$key->nullsComeAfter);

        self::whereLeadingKeyNotBeforeCursor($builder, $keys->first());

        if ($canCompareRowValues) {
            self::whereRowValuePastCursor($builder, $keys);
        } else {
            self::whereAnyKeyPastCursor($builder, $keys);
        }
    }

    /** @param Collection<int, CursorSortKey> $keys */
    private static function whereRowValuePastCursor(Builder $builder, Collection $keys): void
    {
        $grammar = $builder->getQuery()->getGrammar();

        $columns = $keys->map(static fn (CursorSortKey $key) => $grammar->getValue($key->expression))->implode(', ');
        $placeholders = $keys->map(static fn () => '?')->implode(', ');
        $operator = $keys->first()->ascending ? '>' : '<';

        $builder->whereRaw("($columns) $operator ($placeholders)", $keys->pluck('value')->all());
    }

    /**
     * Implied by the full condition; only there so the database can seek on the leading sort column's index.
     * MySQL and MariaDB never seek on a row-value comparison alone.
     */
    private static function whereLeadingKeyNotBeforeCursor(Builder $builder, CursorSortKey $key): void
    {
        if ($key->value === null || $key->nullsComeAfter) {
            return;
        }

        $builder->where($key->expression, $key->ascending ? '>=' : '<=', $key->value);
    }

    /** @param Collection<int, CursorSortKey> $keys */
    private static function whereAnyKeyPastCursor(Builder $builder, Collection $keys): void
    {
        $builder->where(static function (Builder $query) use ($keys): void {
            $keys->each(static function (CursorSortKey $key, int $index) use ($query, $keys): void {
                if ($key->value === null && $key->nullsComeAfter) {
                    return;
                }

                $query->orWhere(static function (Builder $branch) use ($key, $index, $keys): void {
                    $keys->take($index)->each(static fn (CursorSortKey $equalKey) => $equalKey->value === null
                        ? $branch->whereNull($equalKey->expression)
                        : $branch->where($equalKey->expression, '=', $equalKey->value));

                    self::whereKeyPastCursor($branch, $key);
                });
            });
        });
    }

    private static function whereKeyPastCursor(Builder $query, CursorSortKey $key): void
    {
        if ($key->value === null) {
            $query->whereNotNull($key->expression);

            return;
        }

        $query->where(static function (Builder $comparison) use ($key): void {
            $comparison->where($key->expression, $key->ascending ? '>' : '<', $key->value);

            if ($key->nullsComeAfter) {
                $comparison->orWhereNull($key->expression);
            }
        });
    }
}
