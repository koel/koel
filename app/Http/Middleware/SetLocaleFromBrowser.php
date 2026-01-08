<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromBrowser
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['en', 'es', 'vi'];
        $preferredLocale = $this->getPreferredLocale($request, $supportedLocales);

        if ($preferredLocale) {
            app()->setLocale($preferredLocale);
        }

        return $next($request);
    }

    private function getPreferredLocale(Request $request, array $supportedLocales): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        $languages = $this->parseAcceptLanguage($acceptLanguage);

        foreach ($languages as $language) {
            $locale = $this->normalizeLocale($language);

            if (in_array($locale, $supportedLocales, true)) {
                return $locale;
            }
        }

        return null;
    }

    private function parseAcceptLanguage(string $acceptLanguage): array
    {
        $languages = [];

        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', trim($lang));
            $locale = trim($parts[0]);

            $quality = 1.0;
            if (isset($parts[1]) && str_starts_with($parts[1], 'q=')) {
                $quality = (float) substr($parts[1], 2);
            }

            $languages[] = ['locale' => $locale, 'quality' => $quality];
        }

        usort($languages, static fn ($a, $b) => $b['quality'] <=> $a['quality']);

        return array_column($languages, 'locale');
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        if (str_contains($locale, '-')) {
            return explode('-', $locale)[0];
        }

        if (str_contains($locale, '_')) {
            return explode('_', $locale)[0];
        }

        return $locale;
    }
}
