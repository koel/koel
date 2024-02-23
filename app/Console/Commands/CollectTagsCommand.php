<?php

namespace App\Console\Commands;

use App\Models\Song;
use Illuminate\Console\Command;

class CollectTagsCommand extends Command
{
    protected $signature = 'koel:tags:collect {tag*}';
    protected $description = 'Collect additional tags from existing songs';

    private const ALL_TAGS = [
        'title',
        'album',
        'artist',
        'albumartist',
        'track',
        'disc',
        'year',
        'genre',
        'lyrics',
        'cover',
    ];

    private const COLLECTABLE_TAGS = ['year', 'genre'];

    public function handle(): int
    {
        if (config('koel.storage_driver') !== 'local') {
            $this->components->error('This command only works with the local storage driver.');

            return self::INVALID;
        }

        $tags = collect($this->argument('tag'))->unique();

        if ($tags->diff(self::COLLECTABLE_TAGS)->isNotEmpty()) {
            $this->error(
                sprintf(
                    'Invalid tag(s): %s. Allowed tags are: %s.',
                    $tags->diff(self::COLLECTABLE_TAGS)->join(', '),
                    implode(', ', self::COLLECTABLE_TAGS)
                )
            );

            return self::FAILURE;
        }

        Song::withoutSyncingToSearch(function () use ($tags): void {
            $this->call('koel:sync', [
                '--force' => true,
                '--ignore' => collect(self::ALL_TAGS)->diff($tags)->all(),
            ]);
        });

        return self::SUCCESS;
    }
}
