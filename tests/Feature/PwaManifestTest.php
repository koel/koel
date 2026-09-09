<?php

namespace Tests\Feature;

use App\Services\PwaManifestService;
use ErrorException;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    public function tearDown(): void
    {
        File::delete([public_path('manifest.json'), public_path('manifest-remote.json')]);

        parent::tearDown();
    }

    #[Test]
    public function servesAppManifest(): void
    {
        config(['app.url' => 'https://music.example.com']);

        $this
            ->get('manifest.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Koel')
            ->assertJsonPath('start_url', 'https://music.example.com/')
            ->assertJsonPath('icons.0.sizes', '192x192')
            ->assertJsonPath('background_color', '#111111')
            ->assertJsonPath('theme_color', '#111111');
    }

    #[Test]
    public function servesRemoteManifest(): void
    {
        config(['app.url' => 'https://music.example.com']);

        $this
            ->get('manifest-remote.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Koel Remote Controller')
            ->assertJsonPath('start_url', 'https://music.example.com/remote');
    }

    #[Test]
    public function startUrlHonorsSubdirectoryInstallation(): void
    {
        config(['app.url' => 'https://example.com/koel']);

        $this->get('manifest.json')->assertJsonPath('start_url', 'https://example.com/koel/');
        $this->get('manifest-remote.json')->assertJsonPath('start_url', 'https://example.com/koel/remote');
    }

    #[Test]
    public function iconIsServedFromCdnWhenConfigured(): void
    {
        config(['koel.cdn.url' => 'https://cdn.example.com']);

        $this->get('manifest.json')->assertJsonPath('icons.0.src', 'https://cdn.example.com/img/icon.png');
    }

    #[Test]
    public function eachPageLinksItsOwnManifest(): void
    {
        $this->withoutVite()->get('/')->assertSee('rel="manifest" href="' . route('manifest') . '"', false);

        $this->withoutVite()->get('remote')->assertSee('rel="manifest" href="' . route('manifest.remote') . '"', false);
    }

    #[Test]
    public function pageThemeColorMatchesTheManifest(): void
    {
        $themeColor = $this->get('manifest.json')->json('theme_color');

        $this->withoutVite()->get('/')->assertSee('<meta name="theme-color" content="' . $themeColor . '">', false);
    }

    #[Test]
    public function removesLegacyManifestsStillPointingAtThePlaceholderHost(): void
    {
        File::put(public_path('manifest.json'), json_encode(['start_url' => 'https://your.koel.host']));
        File::put(public_path('manifest-remote.json'), json_encode(['start_url' => 'https://your.koel.host/remote']));

        app(PwaManifestService::class)->removeUncustomizedLegacyFiles();

        self::assertFalse(File::exists(public_path('manifest.json')));
        self::assertFalse(File::exists(public_path('manifest-remote.json')));
    }

    #[Test]
    public function keepsUnreadableLegacyManifest(): void
    {
        File::put(public_path('manifest.json'), json_encode(['start_url' => 'https://your.koel.host']));

        File::partialMock()
            ->shouldReceive('get')
            ->with(public_path('manifest.json'))
            ->andThrow(new ErrorException('Failed to open stream: Permission denied'));

        app(PwaManifestService::class)->removeUncustomizedLegacyFiles();

        self::assertTrue(File::exists(public_path('manifest.json')));
    }

    #[Test]
    public function keepsMalformedLegacyManifest(): void
    {
        File::put(public_path('manifest.json'), 'not json at all');

        app(PwaManifestService::class)->removeUncustomizedLegacyFiles();

        self::assertTrue(File::exists(public_path('manifest.json')));
    }

    #[Test]
    public function keepsCustomizedLegacyManifest(): void
    {
        $customized = json_encode(['start_url' => 'https://music.example.com', 'name' => 'My Tunes']);
        File::put(public_path('manifest.json'), $customized);

        app(PwaManifestService::class)->removeUncustomizedLegacyFiles();

        self::assertSame($customized, File::get(public_path('manifest.json')));
    }
}
