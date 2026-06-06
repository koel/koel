<?php

namespace App\Rules;

use App\Exceptions\UnsafeUrlException;
use App\Services\Network\SafeHttp;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

/**
 * Validates that a URL serves audio content. Pair with SafeUrl + 'bail' on the
 * field so this rule never runs on a URL the safety check has already rejected.
 */
class HasAudioContentType implements ValidationRule
{
    public function __construct(
        private ?SafeHttp $http = null,
    ) {
        $this->http ??= app(SafeHttp::class);
    }

    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = (string) $value;

        try {
            $contentType = $this->resolveContentType($url);
        } catch (UnsafeUrlException) {
            $fail('The :attribute must point to a public URL.');

            return;
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
        // Try HEAD first — fast and lightweight. Let UnsafeUrlException propagate
        // (don't fall back to GET on safety failures — the URL is blocked, not
        // just HEAD-incompatible).
        try {
            $response = $this->http->head($url);

            if ($response->successful()) {
                return $response->header('Content-Type');
            }
        } catch (UnsafeUrlException $e) {
            throw $e;
        } catch (Throwable) { // @mago-expect lint:no-empty-catch-clause -- HEAD may time out or fail on streaming servers; fall through to GET below.
        }

        // Fall back to GET as a stream with ICY headers — Shoutcast/Icecast only respond to GET
        $response = $this->http->getAsStream($url, ['Icy-MetaData' => '1']);

        return $response->header('Content-Type');
    }
}
