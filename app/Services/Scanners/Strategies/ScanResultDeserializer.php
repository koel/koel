<?php

namespace App\Services\Scanners\Strategies;

use App\Enums\ScanResultType;
use App\Values\Scanning\ScanResult;
use Illuminate\Support\Facades\Log;

class ScanResultDeserializer
{
    public function deserialize(string $json): ?ScanResult
    {
        $data = json_decode(trim($json), true);

        if (!is_array($data) || !isset($data['path'], $data['type'])) {
            Log::debug('Could not parse scan worker output as JSON line', ['line' => $json]);

            return null;
        }

        $type = ScanResultType::tryFrom($data['type']);

        if (!$type) {
            Log::debug('Unknown scan result type from worker output', ['line' => $json]);

            return null;
        }

        return match ($type) {
            ScanResultType::SUCCESS => ScanResult::success($data['path']),
            ScanResultType::SKIPPED => ScanResult::skipped($data['path']),
            ScanResultType::ERROR => ScanResult::error($data['path'], $data['error'] ?? null),
        };
    }
}
