<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use Illuminate\Console\Command;

class ImportSearchableEntitiesCommand extends Command
{
    private const SEARCHABLE_ENTITIES = [
        Song::class,
        Album::class,
        Artist::class,
        Playlist::class,
    ];

    protected $signature = 'search:import';
    protected $description = 'Import all searchable entities with Scout';

    public function handle(): int
    {
        foreach (self::SEARCHABLE_ENTITIES as $entity) {
            if (!class_exists($entity)) {
                continue;
            }

            $this->call('scout:import', ['model' => $entity]);
        }

        return 0;
    }
}
