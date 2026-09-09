<?php

namespace Tests\Unit\Services;

use App\Services\PublicStorageLinker;
use ErrorException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicStorageLinkerTest extends TestCase
{
    private string $root;
    private string $link;
    private string $target;
    private PublicStorageLinker $linker;

    public function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/koel_storage_link_' . Str::random();
        $this->link = $this->root . '/public/storage';
        $this->target = $this->root . '/storage/app/public';

        File::ensureDirectoryExists($this->root . '/public');
        File::ensureDirectoryExists($this->target);

        app()->usePublicPath($this->root . '/public');
        app()->useStoragePath($this->root . '/storage');
        config(['filesystems.links' => [$this->link => $this->target]]);

        $this->linker = new PublicStorageLinker();
    }

    public function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    #[Test]
    public function createsRelativeLinkWhenMissing(): void
    {
        self::assertTrue($this->linker->link());

        self::assertTrue(is_link($this->link));
        self::assertSame(realpath($this->target), realpath($this->link));
        self::assertStringStartsNotWith('/', readlink($this->link));
    }

    #[Test]
    public function recreatesLinkResolvingElsewhere(): void
    {
        File::ensureDirectoryExists($this->root . '/somewhere-else');
        symlink($this->root . '/somewhere-else', $this->link);

        self::assertTrue($this->linker->link());

        self::assertSame(realpath($this->target), realpath($this->link));
    }

    #[Test]
    public function recreatesDanglingLink(): void
    {
        symlink($this->root . '/gone', $this->link);

        self::assertTrue($this->linker->link());

        self::assertSame(realpath($this->target), realpath($this->link));
    }

    #[Test]
    public function leavesUpToDateAbsoluteLinkUntouched(): void
    {
        symlink($this->target, $this->link);

        Artisan::partialMock()->shouldNotReceive('call')->with('storage:link', Mockery::any());

        self::assertTrue($this->linker->link());
        self::assertSame($this->target, readlink($this->link));
    }

    #[Test]
    public function leavesUpToDateRelativeLinkUntouched(): void
    {
        symlink('../storage/app/public', $this->link);

        Artisan::partialMock()->shouldNotReceive('call')->with('storage:link', Mockery::any());

        self::assertTrue($this->linker->link());
        self::assertSame('../storage/app/public', readlink($this->link));
    }

    #[Test]
    public function returnsFalseWhenLinkingThrows(): void
    {
        Artisan::partialMock()
            ->shouldReceive('call')
            ->once()
            ->with('storage:link', Mockery::any())
            ->andThrow(new ErrorException('symlink(): File exists'));

        self::assertFalse($this->linker->link());
    }
}
