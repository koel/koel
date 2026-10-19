<?php

namespace App\Repositories\Pagination;

use App\Repositories\Contracts\PaginationStrategy;
use App\Values\Pagination\CursorSortKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class CursorStrategy implements PaginationStrategy
{
    public function __construct(
        private ?string $cursor,
    ) {}

    public function apply(Builder $builder, string $idColumn, int $perPage): CursorPaginator
    {
        $cursor = $this->cursor ? Cursor::fromEncoded($this->cursor) : null;

        $builder->orderBy($idColumn, self::getLeadingDirection($builder));

        if ($cursor?->pointsToPreviousItems()) {
            self::reverseOrders($builder);
        }

        $orders = collect($builder->getQuery()->orders)
            ->filter(static fn (array $order) => Arr::has($order, 'direction'))
            ->values();

        if ($cursor) {
            CursorCondition::wherePastCursor($builder, self::makeSortKeys($builder, $orders, $cursor));
        }

        return new StoredValueCursorPaginator($builder->limit($perPage + 1)->get(), $perPage, $cursor, [
            'path' => Paginator::resolveCurrentPath(),
            'cursorName' => 'cursor',
            'parameters' => $orders->pluck('column')->all(),
        ]);
    }

    private static function getLeadingDirection(Builder $builder): string
    {
        $firstOrder = collect($builder->getQuery()->orders)
            ->first(static fn (array $order) => Arr::has($order, 'direction'));

        return $firstOrder['direction'] ?? 'asc';
    }

    private static function reverseOrders(Builder $builder): void
    {
        $builder->getQuery()->orders = collect($builder->getQuery()->orders)->map(static function (array $order): array {
            if (Arr::has($order, 'direction')) {
                $order['direction'] = $order['direction'] === 'asc' ? 'desc' : 'asc';
            }

            return $order;
        })->all();
    }

    /**
     * @param Collection<int, array{column: string, direction: string}> $orders
     *
     * @return Collection<int, CursorSortKey>
     */
    private static function makeSortKeys(Builder $builder, Collection $orders, Cursor $cursor): Collection
    {
        $nullsSortLowest = !$builder->getQuery()->getGrammar() instanceof PostgresGrammar;
        $sortsNullsLast = static fn (array $order) => ($order['direction'] === 'asc') !== $nullsSortLowest;

        $notNullColumns = $orders->contains($sortsNullsLast) ? self::getNotNullColumns($builder) : [];

        return $orders->map(static fn (array $order) => CursorSortKey::make(
            expression: self::resolveSortExpression($builder, $order['column']),
            value: $cursor->parameter($order['column']),
            ascending: $order['direction'] === 'asc',
            nullsComeAfter: $sortsNullsLast($order) && !in_array($order['column'], $notNullColumns, true),
        ));
    }

    /**
     * Only the queried table's own columns: a joined table's NOT NULL column can still come back NULL.
     *
     * @return array<int, string>
     */
    private static function getNotNullColumns(Builder $builder): array
    {
        $table = $builder->getModel()->getTable();

        return collect($builder->getModel()->getConnection()->getSchemaBuilder()->getColumns($table))
            ->reject(static fn (array $column) => $column['nullable'])
            ->map(static fn (array $column) => "$table.{$column['name']}")
            ->values()
            ->all();
    }

    private static function resolveSortExpression(Builder $builder, string $column): Expression
    {
        $query = $builder->getQuery();
        $grammar = $query->getGrammar();

        foreach ($query->getColumns() as $select) {
            $aliasPosition = strripos($select, ' as ');

            if ($aliasPosition === false) {
                continue;
            }

            $alias = substr($select, $aliasPosition + 4);

            if ($alias === $column || $alias === $grammar->wrap($column)) {
                $original = substr($select, 0, $aliasPosition);

                return new Expression(Str::contains($original, ['(', ')']) ? $original : $grammar->wrap($original));
            }
        }

        return new Expression($grammar->wrap($column));
    }
}
