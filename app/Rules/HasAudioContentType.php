<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

/**
 * Validates that a URL serves audio content.
 * Should be used after SafeUrl to ensure the URL is safe to reach.
 */
class HasAudioContentType implements ValidationRule
{
    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = (string) $value;

        try {
            $contentType = $this->resolveContentType($url);
        } catch (Throwable) {
            $fail("The $attribute couldn't be reached.");

            return;
        }

        if (!$contentType || !Str::startsWith($contentType, 'audio/')) {
            $fail("The $attribute doesn't look like a valid radio station URL.");
        }
    }

    /**
     * Resolve the Content-Type of a URL.
     * Many streaming servers (Shoutcast/Icecast) don't support HEAD requests,
     * so we fall back to a GET with streaming headers if HEAD fails.
     */
    private function resolveContentType(string $url): string
    {
        // Try HEAD first — fast and lightweight
        try {
            $response = Http::head($url);

            if ($response->successful()) {
                return $response->header('Content-Type');
            }
        } catch (Throwable) { // @mago-expect lint:no-empty-catch-clause -- HEAD may time out or fail on streaming servers; fall through to GET below.
        }

        // Fall back to GET with ICY headers — streaming servers often only respond to GET
        $response = Http::withHeaders(['Icy-MetaData' => '1'])->withOptions(['stream' => true])->get($url);

        return $response->header('Content-Type');
    }
}
