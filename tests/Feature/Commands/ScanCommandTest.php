<?php

namespace Tests\Feature\Commands;

use App\Models\Setting;
use App\Services\Scanners\DirectoryScanner;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanSummary;
use Illuminate\Console\Command;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class ScanCommandTest extends TestCase
{
    #[Test]
    public function storesCanonicalMediaPathWhenAskedForOne(): void
    {
        config(['koel.storage_driver' => 'local']);
        Setting::set('media_path', '');
        create_admin();

        $scanner = Mockery::mock(DirectoryScanner::class);
        $scanner->allows('on');
        $scanner->expects('scan')->andReturn(ScanSummary::make('scan-id', 0, 0, 0));
        $this->instance(DirectoryScanner::class, $scanner);

        $this
            ->artisan('koel:scan')
            ->expectsQuestion('Absolute path to your media directory', $this->mediaPath . '//')
            ->assertSuccessful();

        self::assertSame(realpath($this->mediaPath), Setting::get('media_path'));
    }

    #[Test]
    public function ignoreCommaSeparatedTags(): void
    {
        config(['koel.storage_driver' => 'local']);
        Setting::set('media_path', $this->mediaPath);
        create_admin();

        $scanner = Mockery::mock(DirectoryScanner::class);
        $scanner->allows('on');
        $scanner
            ->expects('scan')
            ->with(
                Mockery::any(),
                Mockery::on(
                    static fn (ScanConfiguration $config) => (
                        $config->ignores === [
                            'album',
                            'genre',
                            'title',
                        ]
                    ),
                ),
                Mockery::any(),
            )
            ->andReturn(ScanSummary::make('scan-id', 0, 0, 0));
        $this->instance(DirectoryScanner::class, $scanner);

        $this->artisan('koel:scan', ['--ignore' => ['title, album', 'genre']])->assertSuccessful();
    }

    #[Test]
    public function failsWithoutAskingWhenNoMediaPathIsSetAndNotInteractive(): void
    {
        config(['koel.storage_driver' => 'local']);
        Setting::set('media_path', '');

        $this->artisan('koel:scan', ['--no-interaction' => true])->assertExitCode(Command::INVALID);

        self::assertSame('', Setting::get('media_path'));
    }
}
