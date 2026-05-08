<?php

namespace Tests\Feature\Migrations;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlaylistUserRelationshipMigrationTest extends TestCase
{
    #[Test]
    public function migrationProducesExpectedSchema(): void
    {
        self::assertTrue(Schema::hasTable('playlist_user'));
        self::assertFalse(Schema::hasTable('playlist_collaborators'));

        self::assertTrue(Schema::hasColumns('playlist_user', [
            'user_id',
            'playlist_id',
            'role',
            'position',
            'created_at',
            'updated_at',
        ]));

        self::assertFalse(Schema::hasColumn('playlists', 'user_id'));
    }

    #[Test]
    public function rerunningTheMigrationIsANoOp(): void
    {
        $migration = require database_path('migrations/2025_06_03_121538_modify_playlist-user_relationship.php');

        $migration->up();

        self::assertTrue(Schema::hasTable('playlist_user'));
    }
}
