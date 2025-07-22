<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("SET sql_mode = ''");
        }

        DB::dropForeignKeyIfExists('albums', 'artist_id');
        DB::dropForeignKeyIfExists('songs', 'artist_id');
        DB::dropForeignKeyIfExists('songs', 'album_id');

        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support dropping columns directly, so we need to
            // 1. create a new table with the same structure but with the new primary key
            // 2. copy the data from the old table to the new table
            // 3. drop the old table
            // 4. rename the new table to the old table name
            Schema::create('artists_new', static function (Blueprint $table): void {
                $table->string('id', 26)->primary();
                $table->string('name');
                $table->string('image')->nullable();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->timestamps();
            });

            DB::table('artists_new')->insert(DB::table('artists')->get()->map(static function ($artist) {
                return [
                    'id' => $artist->public_id,
                    'name' => $artist->name,
                    'image' => $artist->image,
                    'user_id' => $artist->user_id,
                    'created_at' => $artist->created_at,
                    'updated_at' => $artist->updated_at,
                ];
            })->toArray());

            Schema::dropIfExists('artists');
            Schema::rename('artists_new', 'artists');

            // Same for albums
            Schema::create('albums_new', static function (Blueprint $table): void {
                $table->string('id', 26)->primary();
                $table->string('name');
                $table->string('cover')->nullable();
                $table->string('artist_id', 26);
                $table->string('artist_name', 26)->nullable();
                $table->smallInteger('year')->nullable();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->timestamps();
            });

            DB::table('albums_new')->insert(DB::table('albums')->get()->map(static function ($album) {
                return [
                    'id' => $album->public_id,
                    'name' => $album->name,
                    'cover' => $album->cover,
                    'artist_id' => $album->artist_ulid,
                    'artist_name' => $album->artist_name,
                    'year' => $album->year,
                    'user_id' => $album->user_id,
                    'created_at' => $album->created_at,
                    'updated_at' => $album->updated_at,
                ];
            })->toArray());

            Schema::dropIfExists('albums');
            Schema::rename('albums_new', 'albums');
        } else {
            // For other databases, we can just rename the column
            Schema::table('artists', static function (Blueprint $table): void {
                $table->dropColumn('id');
                $table->renameColumn('public_id', 'id');
                $table->primary('id');
            });

            Schema::table('albums', static function (Blueprint $table): void {
                $table->dropColumn(['id', 'artist_id']);
            });

            Schema::table('albums', static function (Blueprint $table): void {
                $table->renameColumn('public_id', 'id');
                $table->renameColumn('artist_ulid', 'artist_id');
            });

            Schema::table('albums', static function (Blueprint $table): void {
                $table->primary('id');
            });
        }

        // Now we can add the foreign key back
        Schema::table('albums', static function (Blueprint $table): void {
            $table->foreign('artist_id')->references('id')->on('artists')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('songs', static function (Blueprint $table): void {
            // since SQLite doesn't support dropping indexed columns, we need to drop the foreign keys first
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign(['artist_id']);
                $table->dropForeign(['album_id']);
            }

            $table->dropColumn(['artist_id', 'album_id']);
            $table->renameColumn('artist_ulid', 'artist_id');
            $table->renameColumn('album_ulid', 'album_id');

            // add the foreign keys back for SQLite
            if (DB::getDriverName() === 'sqlite') {
                $table->foreign('artist_id')->references('id')->on('artists')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign('album_id')->references('id')->on('albums')->cascadeOnDelete()->cascadeOnUpdate();
            }
        });

        Schema::table('songs', static function (Blueprint $table): void {
            $table->foreign('artist_id')->references('id')->on('artists')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('album_id')->references('id')->on('albums')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }
};
