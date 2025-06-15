<?php

use App\Facades\License;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artists', static function (Blueprint $table): void {
            $table->string('public_id', 26)->unique()->nullable();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();

            $table->unique(['name', 'user_id']);
        });

        Schema::table('albums', static function (Blueprint $table): void {
            $table->string('public_id', 26)->unique()->nullable();
            //Strictly saying we don't need user_id, but it's better for simplicity and performance.
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();

            $table->unique(['name', 'artist_id', 'user_id']);
        });

        // set the default user_id for existing albums and artists to the first admin
        $firstAdmin = User::firstAdmin();

        Artist::query()
            ->orderBy('id')
            ->get()
            ->each(static function (Artist $artist) use ($firstAdmin): void {
                $artist->update([
                    'user_id' => $firstAdmin->id,
                    'public_id' => Ulid::generate(),
                ]);
            });

        Album::query()
            ->orderBy('id')
            ->get()
            ->each(static function (Album $album) use ($firstAdmin): void {
                $album->update([
                    'user_id' => $firstAdmin->id,
                    'public_id' => Ulid::generate(),
                ]);
            });

        // For the CE, we stop here. All artists and albums are owned by the default user and shared across all users.
        if (License::isCommunity()) {
            return;
        }

        // For Koel Plus, we need to update songs that are not owned by the default user to
        // have their corresponding artist and album owned by the same user.
        $songsNotOwnedByDefaultUser = Song::query()
            ->with('artist', 'album')
            ->whereNull('podcast_id')
            ->where('owner_id', '!=', $firstAdmin)
            ->get();

        foreach ($songsNotOwnedByDefaultUser as $song) {
            $artist = Artist::query()->firstOrCreate([
                'name' => $song->artist->name,
                'user_id' => $song->owner_id,
            ], [
                'image' => $song->artist->image,
            ]);

            $album = Album::query()->firstOrCreate([
                'name' => $song->album->name,
                'artist_id' => $artist->id,
                'user_id' => $song->owner_id,
            ], [
                'cover' => $song->album->cover,
            ]);

            $song->artist_id = $artist->id;
            $song->album_id = $album->id;
            $song->save();
        }
    }
};
