<?php

namespace Tests\Feature\Commands;

use App\Models\Setting;
use App\Services\Scanners\DirectoryScanner;
use App\Values\Scanning\ScanResultCollection;
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
        $scanner->expects('scan')->andReturn(ScanResultCollection::create());
        $this->instance(DirectoryScanner::class, $scanner);

        $this
            ->artisan('koel:scan')
            ->expectsQuestion('Absolute path to your media directory', $this->mediaPath . '//')
            ->assertSuccessful();

        self::assertSame(realpath($this->mediaPath), Setting::get('media_path'));
    }
}
