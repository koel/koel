<?php

namespace App\Repositories;

use App\Enums\ScanResultType;
use App\Models\ScannedFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/** @extends Repository<ScannedFile> */
class ScannedFileRepository extends Repository
{
    /** @return LazyCollection<int, ScannedFile> */
    public function lazyGetByScanId(string $scanId, ?ScanResultType $type = null): LazyCollection
    {
        return ScannedFile::query()
            ->where('scan_id', $scanId)
            ->when($type, static fn (Builder $query): Builder => $query->where('type', $type))
            ->lazyById();
    }

    /** @return LazyCollection<int, string> */
    public function lazyGetUnskippedPathsByScanId(string $scanId): LazyCollection
    {
        return ScannedFile::query()
            ->where('scan_id', $scanId)
            ->where('type', '!=', ScanResultType::SKIPPED)
            ->select(['id', 'path'])
            ->lazyById()
            ->map(static fn (ScannedFile $file): string => $file->path);
    }

    /** @return array<string> */
    public function getIdsOfScansInactiveSince(Carbon $since): array
    {
        return ScannedFile::query()
            ->groupBy('scan_id')
            ->havingRaw('MAX(created_at) < ?', [$since])
            ->pluck('scan_id')
            ->all();
    }
}
