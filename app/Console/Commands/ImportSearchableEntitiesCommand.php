<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\Song;
use Illuminate\Console\Command;

class ImportSearchableEntitiesCommand extends Command
{
    private const SEARCHABLE_ENTITIES = [
        Song::class,
        Album::class,
        Artist::class,
        Playlist::class,
        Podcast::class,
    ];

    protected $signature = 'koel:search:import';
    protected $description = 'Import all searchable entities with Scout';

    public function handle(): int
    {
        foreach (self::SEARCHABLE_ENTITIES as $entity) {
            $this->call('scout:import', ['model' => $entity]);
        }

        return self::SUCCESS;
    }
}
