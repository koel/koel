<?php

namespace App\Services\Playlist;

use App\Enums\Placement;
use App\Models\Playlist;
use App\Repositories\PlaylistRepository;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class PlaylistPlayableMover
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
    ) {}

    /** @param array<string> $movingIds */
    public function movePlayables(Playlist $playlist, array $movingIds, string $target, Placement $placement): void
    {
        DB::transaction(function () use ($playlist, $movingIds, $target, $placement): void {
            $positionsById = $this->playlistRepository->getPlayablePositionsInPlaylist($playlist, [
                ...$movingIds,
                $target,
            ]);

            $targetPosition = $positionsById->get($target);
            $movingIds = array_values(array_unique(array_filter($movingIds, $positionsById->has(...))));

            if ($targetPosition === null || !$movingIds) {
                return;
            }

            $insertPosition = $placement === Placement::BEFORE ? $targetPosition : $targetPosition + 1;

            [$movingPositionsBefore, $movingPositionsAfter] = $positionsById
                ->only($movingIds)
                ->sort()
                ->values()
                ->partition(static fn (int $position): bool => $position < $insertPosition);

            self::shiftPlayablesAroundInsertPosition(
                $playlist,
                $movingIds,
                $insertPosition,
                $movingPositionsBefore->values()->all(),
                $movingPositionsAfter->values()->all(),
            );

            $nextPosition = $insertPosition - $movingPositionsBefore->count();

            foreach ($movingIds as $id) {
                $playlist
                    ->playables()
                    ->newPivotQuery()
                    ->where('song_id', $id)
                    ->update(['position' => $nextPosition++]);
            }
        });
    }

    /**
     * Only the rows between the moving rows and the insert position shift; the rest keep their positions.
     *
     * @param array<string> $movingIds
     * @param list<int> $movingPositionsBefore sorted
     * @param list<int> $movingPositionsAfter sorted
     */
    private static function shiftPlayablesAroundInsertPosition(
        Playlist $playlist,
        array $movingIds,
        int $insertPosition,
        array $movingPositionsBefore,
        array $movingPositionsAfter,
    ): void {
        $cases = [];
        $upperBoundsBefore = [...array_slice($movingPositionsBefore, 1), $insertPosition];

        foreach ($movingPositionsBefore as $index => $lowerBound) {
            $cases[] = sprintf(
                'WHEN position > %d AND position < %d THEN position - %d',
                $lowerBound,
                $upperBoundsBefore[$index],
                $index + 1,
            );
        }

        $lowerBoundsAfter = [$insertPosition - 1, ...$movingPositionsAfter];
        $countAfter = count($movingPositionsAfter);

        foreach ($movingPositionsAfter as $index => $upperBound) {
            $cases[] = sprintf(
                'WHEN position > %d AND position < %d THEN position + %d',
                $lowerBoundsAfter[$index],
                $upperBound,
                $countAfter - $index,
            );
        }

        $playlist
            ->playables()
            ->newPivotQuery()
            ->whereBetween('position', [
                $movingPositionsBefore[0] ?? $insertPosition,
                $movingPositionsAfter[$countAfter - 1] ?? $insertPosition,
            ])
            ->whereNotIn('song_id', $movingIds)
            ->update(['position' => new Expression('CASE ' . implode(' ', $cases) . ' ELSE position END')]);
    }
}
