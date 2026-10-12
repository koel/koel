<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * MySQL indexes foreign key columns on its own; SQLite doesn't, and Postgres misses a few.
     *
     * @var array<string, list<string>>
     */
    private const array FOREIGN_KEY_COLUMNS = [
        'songs' => ['album_id', 'artist_id', 'owner_id', 'podcast_id'],
        'albums' => ['artist_id', 'user_id'],
        'artists' => ['user_id'],
        'plays' => ['song_id'],
        'playlist_song' => ['song_id'],
        'playlist_user' => ['playlist_id', 'user_id'],
        'playlist_playlist_folder' => ['playlist_id'],
        'playlist_folders' => ['user_id'],
        'playlist_collaboration_tokens' => ['playlist_id'],
        'podcasts' => ['added_by'],
        'podcast_user' => ['podcast_id'],
        'queue_states' => ['user_id', 'current_song_id'],
        'radio_stations' => ['user_id'],
        'embeds' => ['user_id'],
        'duplicate_uploads' => ['user_id', 'existing_song_id'],
        'users' => ['invited_by_id'],
        'role_has_permissions' => ['role_id'],
    ];

    public function up(): void
    {
        foreach (self::FOREIGN_KEY_COLUMNS as $table => $columns) {
            $unindexedColumns = array_values(array_filter(
                $columns,
                static fn (string $column): bool => !self::hasIndexLeadingWithColumn($table, $column),
            ));

            if (!$unindexedColumns) {
                continue;
            }

            Schema::table($table, static function (Blueprint $blueprint) use ($unindexedColumns): void {
                foreach ($unindexedColumns as $column) {
                    $blueprint->index($column);
                }
            });
        }
    }

    private static function hasIndexLeadingWithColumn(string $table, string $column): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(static fn (array $index): bool => Arr::first($index['columns']) === $column);
    }
};
