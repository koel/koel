<?php

namespace Tests\Unit\Services;

use App\Services\DotenvEditor;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DotenvEditorTest extends TestCase
{
    private string $envPath;
    private DotenvEditor $editor;

    public function setUp(): void
    {
        parent::setUp();

        $this->envPath = tempnam(sys_get_temp_dir(), 'dotenv_test_');
        file_put_contents($this->envPath, "APP_NAME=Koel\nDB_HOST=localhost\n");

        $this->editor = new DotenvEditor($this->envPath, new Filesystem());
    }

    public function tearDown(): void
    {
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function setKeyOverwritesExistingValue(): void
    {
        $this->editor->setKey('APP_NAME', 'NewName');

        self::assertStringContainsString('APP_NAME=NewName', file_get_contents($this->envPath));
    }

    #[Test]
    public function setKeyAppendsWhenKeyDoesNotExist(): void
    {
        $this->editor->setKey('NEW_KEY', 'NewValue');

        self::assertStringContainsString('NEW_KEY=NewValue', file_get_contents($this->envPath));
    }

    #[Test]
    public function setKeysWritesMultipleVariables(): void
    {
        $this->editor->setKeys([
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
        ]);

        $contents = file_get_contents($this->envPath);
        self::assertStringContainsString('DB_HOST="127.0.0.1"', $contents);
        self::assertStringContainsString('DB_PORT=5432', $contents);
    }

    #[Test]
    public function backupRestoreRoundTripsTheFile(): void
    {
        $original = file_get_contents($this->envPath);

        $this->editor
            ->backup()
            ->setKeys([
                'APP_NAME' => 'Mutated',
                'DB_HOST' => 'mutated',
            ]);

        self::assertNotSame($original, file_get_contents($this->envPath));

        $this->editor->restore();

        self::assertSame($original, file_get_contents($this->envPath));
    }

    #[Test]
    public function restoreThrowsWhenNoBackupCaptured(): void
    {
        $this->expectException(LogicException::class);

        $this->editor->restore();
    }

    #[Test]
    public function restoreClearsTheBackupSoItCannotBeReused(): void
    {
        $this->editor->backup()->restore();

        $this->expectException(LogicException::class);

        $this->editor->restore();
    }

    #[Test]
    public function setKeyReturnsSelfForChaining(): void
    {
        $result = $this->editor->setKey('FOO', 'bar');

        self::assertSame($this->editor, $result);
    }
}
