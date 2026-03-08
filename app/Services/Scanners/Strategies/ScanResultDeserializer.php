<?php

namespace App\Services\Scanners\Strategies;

use App\Enums\ScanResultType;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;

class ScanResultDeserializer
{
    public function handleLine(string $json, ScanResultCollection $results, ?callable $onProgress): void
    {
        $result = $this->deserialize($json);

        if (!$result) {
            return;
        }

        $results->add($result);

        if ($onProgress) {
            $onProgress($result);
        }
    }

    private function deserialize(string $json): ?ScanResult
    {
        $data = json_decode(trim($json), true);

        if (!is_array($data) || !isset($data['path'], $data['type'])) {
            return null;
        }

        $type = ScanResultType::tryFrom($data['type']);

        if (!$type) {
            return null;
        }

        return match ($type) {
            ScanResultType::SUCCESS => ScanResult::success($data['path']),
            ScanResultType::SKIPPED => ScanResult::skipped($data['path']),
            ScanResultType::ERROR => ScanResult::error($data['path'], $data['error'] ?? null),
        };
    }
}
