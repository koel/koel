<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidRadioStationUrl implements ValidationRule
{
    // Aid in testing by allowing the rule to be bypassed.
    // No need for overengineered abstractions and factories.
    public bool $bypass = false;

    /**
     * Valid content types for radio streams.
     *
     * @var array<string>
     */
    private const VALID_CONTENT_TYPES = [
        'audio/',
        'application/ogg',
        'application/vnd.apple.mpegurl',
        'application/x-mpegurl',
        'video/mp2t', // Some radio streams use this
    ];

    /**
     * Run the validation rule.
     *
     * @param string $value The url to validate
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->bypass) {
            return;
        }

        $result = $this->getContentType($value);

        // If we got a valid content type, check if it's valid
        if ($result['contentType'] && $this->isValidContentType($result['contentType'])) {
            return;
        }

        // If the server responded (even with an error), the URL might still be valid
        // Some streaming servers don't respond well to HEAD/GET but work when played
        // Only fail if there was a clear connection error
        if ($result['serverResponded']) {
            return;
        }

        // Connection failed or no valid content type
        $fail("The $attribute doesn't look like a valid radio station URL.");
    }

    /**
     * Get the Content-Type header from the URL.
     * Tries HEAD first, then GET if HEAD fails.
     *
     * @param string $url
     * @return array{contentType: string|null, serverResponded: bool}
     */
    private function getContentType(string $url): array
    {
        try {
            // Try HEAD first (more efficient)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
                ])
                ->head($url);
            
            // Server responded (even if with an error)
            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                if ($contentType) {
                    return [
                        'contentType' => $this->extractContentType($contentType),
                        'serverResponded' => true,
                    ];
                }
            }
            
            // Server responded but maybe with an error - still counts as server responding
            return [
                'contentType' => null,
                'serverResponded' => true,
            ];
        } catch (\Exception $e) {
            // HEAD failed, try GET
        }

        try {
            // Some servers don't support HEAD, try GET but only read headers
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
                ])
                ->withoutRedirecting()
                ->get($url);
            
            // Server responded (even if with an error)
            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                if ($contentType) {
                    return [
                        'contentType' => $this->extractContentType($contentType),
                        'serverResponded' => true,
                    ];
                }
            }
            
            // Server responded but maybe with an error - still counts as server responding
            return [
                'contentType' => null,
                'serverResponded' => true,
            ];
        } catch (\Exception $e) {
            // Connection failed - this is a real problem
        }

        return [
            'contentType' => null,
            'serverResponded' => false,
        ];
    }

    /**
     * Extract the base content type from a header value (remove charset, etc.).
     *
     * @param string $contentType
     * @return string
     */
    private function extractContentType(string $contentType): string
    {
        // Remove charset and other parameters (e.g., "audio/mpeg; charset=utf-8" -> "audio/mpeg")
        return trim(explode(';', $contentType)[0]);
    }

    /**
     * Check if the content type is valid for a radio stream.
     *
     * @param string $contentType
     * @return bool
     */
    private function isValidContentType(string $contentType): bool
    {
        foreach (self::VALID_CONTENT_TYPES as $validType) {
            if (Str::startsWith($contentType, $validType)) {
                return true;
            }
        }

        return false;
    }
}
