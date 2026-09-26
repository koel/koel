<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CleanUrlsTest extends TestCase
{
    #[Test]
    public function serveTheAppForAScreenPathWhenEnabled(): void
    {
        config(['koel.clean_urls.enabled' => true]);

        $this->get('albums/123')->assertOk()->assertViewIs('index');
    }

    #[Test]
    public function leaveUnknownPathsAloneWhenDisabled(): void
    {
        config(['koel.clean_urls.enabled' => false]);

        $this->get('albums/123')->assertNotFound();
    }

    #[Test]
    public function keepUnknownApiPathsAsApiErrors(): void
    {
        config(['koel.clean_urls.enabled' => true]);

        $this->getJson('api/not-a-thing')->assertStatus(Response::HTTP_NOT_FOUND)->assertJsonStructure(['message']);
    }

    #[Test]
    public function tellTheAppWhetherCleanUrlsAreOn(): void
    {
        config(['koel.clean_urls.enabled' => true]);

        $this->get('/')->assertOk()->assertSee('"clean_urls":true', escape: false);
    }

    /** @return array<string, array{0: string}> */
    public static function provideScreenPaths(): array
    {
        preg_match_all(
            "/path: '([^']+)'/",
            file_get_contents(dirname(__DIR__, 2) . '/resources/assets/js/config/routes.ts'),
            $matches,
        );

        return collect($matches[1])
            ->mapWithKeys(static fn (string $path): array => [
                $path => ['/' . ltrim(preg_replace('/:\\w+\\??/', 'sample', $path), '/')],
            ])->all();
    }

    #[DataProvider('provideScreenPaths')]
    #[Test]
    public function leaveEveryScreenPathToTheApp(string $path): void
    {
        $route = app('router')->getRoutes()->match(Request::create($path));

        self::assertTrue($route->isFallback, "The server route {$route->uri()} takes over the screen path $path.");
    }
}
