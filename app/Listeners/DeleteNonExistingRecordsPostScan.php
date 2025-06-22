<?php

namespace App\Listeners;

use App\Events\MediaScanCompleted;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\Scanning\ScanResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

readonly class DeleteNonExistingRecordsPostScan implements ShouldQueue
{
    public function __construct(private SongRepository $songRepository)
    {
    }

    public function handle(MediaScanCompleted $event): void
    {
        $paths = $event->results
            ->valid()
            ->map(static fn (ScanResult $result) => $result->path)
            ->merge($this->songRepository->getAllStoredOnCloud()->pluck('path'))
            ->toArray();

        Song::deleteWhereValueNotIn($paths, 'path', static function (Builder $builder): Builder {
            return $builder->whereNull('podcast_id');
        });
    }
}
