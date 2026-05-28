<?php

namespace App\Http\Middleware;

use App\Helpers\QueryStringParser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeSubsonicArrayParams
{
    public function handle(Request $request, Closure $next): Response
    {
        // We read the raw $_SERVER value rather than $request->getQueryString()
        // because Symfony's helper normalizes the string through HeaderUtils +
        // http_build_query, which collapses duplicate keys to last-wins —
        // exactly the input shape this middleware exists to recover.
        $parsed = QueryStringParser::parse((string) $request->server('QUERY_STRING'));

        if ($request->isMethod('POST')) {
            $parsed = array_merge_recursive($parsed, QueryStringParser::parse((string) $request->getContent()));
        }

        foreach ($parsed as $key => $values) {
            if (count($values) <= 1) {
                continue;
            }

            $request->query->set($key, $values);
            $request->request->set($key, $values);
            $request->merge([$key => $values]);
        }

        return $next($request);
    }
}
