<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeSubsonicArrayParams
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::collectMultiValueParams($request) as $key => $values) {
            $request->query->set($key, $values);
            $request->request->set($key, $values);
            $request->merge([$key => $values]);
        }

        return $next($request);
    }

    /** @return array<string, list<string>> */
    private static function collectMultiValueParams(Request $request): array
    {
        $sources = array_filter([
            (string) $request->server('QUERY_STRING'),
            $request->isMethod('POST') ? (string) $request->getContent() : '',
        ]);

        $collected = [];

        foreach ($sources as $source) {
            foreach (explode('&', $source) as $pair) {
                if (!str_contains($pair, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $pair, 2);
                $key = urldecode($key);

                if (str_ends_with($key, '[]')) {
                    continue;
                }

                $collected[$key][] = urldecode($value);
            }
        }

        return array_filter($collected, static fn (array $values) => count($values) > 1);
    }
}
