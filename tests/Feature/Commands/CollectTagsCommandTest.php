<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\CollectTagsCommand;
use App\Models\Setting;
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
        Setting::set('media_path', '/tmp');
        create_admin();

        $this->artisan('koel:tags:collect', ['tag' => ['year', 'genre']])->assertSuccessful();
    }
}
