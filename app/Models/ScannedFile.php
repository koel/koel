<?php

namespace App\Models;

use App\Enums\ScanResultType;
use App\Values\Scanning\ScanResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $scan_id
 * @property string $path
 * @property string $path_hash
 * @property ScanResultType $type
 * @property ?string $error
 * @property Carbon $created_at
 */
#[Guarded(['id'])]
class ScannedFile extends Model
{
    public $timestamps = false;

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type' => ScanResultType::class,
            'created_at' => 'datetime',
        ];
    }

    public function toScanResult(): ScanResult
    {
        return match ($this->type) {
            ScanResultType::SUCCESS => ScanResult::success($this->path),
            ScanResultType::SKIPPED => ScanResult::skipped($this->path),
            ScanResultType::ERROR => ScanResult::error($this->path, $this->error),
        };
    }
}
