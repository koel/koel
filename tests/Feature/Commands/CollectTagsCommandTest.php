<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\CollectTagsCommand;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class CollectTagsCommandTest extends TestCase
{
    #[Test]
    public function rejectNonLocalStorage(): void
    {
        config(['koel.storage_driver' => 's3']);

        $this->artisan('koel:tags:collect', ['tag' => ['year']])->assertExitCode(CollectTagsCommand::INVALID);
    }

    #[Test]
    public function rejectInvalidTags(): void
    {
        config(['koel.storage_driver' => 'local']);

        $this->artisan('koel:tags:collect', ['tag' => ['invalid_tag']])->assertFailed();
    }

    #[Test]
    public function acceptValidTags(): void
    {
        config(['koel.storage_driver' => 'local']);
        $emptyMediaPath = sys_get_temp_dir() . '/' . Str::uuid();
        File::ensureDirectoryExists($emptyMediaPath);
        Setting::set('media_path', $emptyMediaPath);
        create_admin();

        $this->artisan('koel:tags:collect', ['tag' => ['year', 'genre']])->assertSuccessful();

        File::deleteDirectory($emptyMediaPath);
    }
}
