<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LibraryManager
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SongRepository $songRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
    ) {}

    /**
     * Before a user is deleted, give the songs they got from a library scan, and the artists and albums those
     * and other users' songs still use, to another admin. Their uploads are left to be deleted with them.
     */
    public function handOverScannedLibraryOfUser(User $user): void
    {
        $heir = $this->userRepository->findOldestAdminOtherThan($user);

        if (!$heir) {
            return;
        }

        DB::transaction(function () use ($user, $heir): void {
            foreach (array_chunk($this->songRepository->getIdsOfScannedSongsOwnedBy($user), 1_000) as $ids) {
                Song::query()->whereKey($ids)->update(['owner_id' => $heir->id]);
            }

            foreach ($this->artistRepository->getOwnedByUserButUsedByOthers($user) as $artist) {
                $this->handOverArtist($artist, $heir);
            }

            foreach ($this->albumRepository->getOwnedByUserButUsedByOthers($user) as $album) {
                $this->handOverAlbum($album, $heir);
            }
        });
    }

    private function handOverArtist(Artist $artist, User $heir): void
    {
        $heirsArtist = License::isPlus()
            ? $this->artistRepository->findOneBy(['user_id' => $heir->id, 'name' => $artist->name])
            : null;

        if (!$heirsArtist) {
            $artist->update(['user_id' => $heir->id]);

            return;
        }

        Song::query()->where('artist_id', $artist->id)->update(['artist_id' => $heirsArtist->id]);
        Album::query()->where('artist_id', $artist->id)->update(['artist_id' => $heirsArtist->id]);
    }

    private function handOverAlbum(Album $album, User $heir): void
    {
        $heirsAlbum = License::isPlus()
            ? $this->albumRepository->findOneBy([
                'user_id' => $heir->id,
                'artist_id' => $album->artist_id,
                'name' => $album->name,
            ])
            : null;

        if (!$heirsAlbum) {
            $album->update(['user_id' => $heir->id]);

            return;
        }

        Song::query()->where('album_id', $album->id)->update(['album_id' => $heirsAlbum->id]);
    }

    public function deleteLocalSongsMissingFromScan(string $scanId): int
    {
        $songsToDelete = Song::query()->storedLocally()->missingFromScan($scanId);

        $songsToDelete->chunkById(
            500,
            static fn (Collection $songs) => $songsToDelete->getModel()->queueRemoveFromSearch($songs),
            'songs.id',
            'id',
        );

        return $songsToDelete->delete();
    }

    /**
     * Delete albums and artists that have no songs.
     *
     * @return array<mixed>
     */
    public function prune(bool $dryRun = false): array
    {
        return DB::transaction(static function () use ($dryRun): array {
            $albumQuery = Album::query()
                ->leftJoin('songs', 'songs.album_id', '=', 'albums.id')
                ->whereNull('songs.album_id');

            $artistQuery = Artist::query()
                ->leftJoin('songs', 'songs.artist_id', '=', 'artists.id')
                ->leftJoin('albums', 'albums.artist_id', '=', 'artists.id')
                ->whereNull('songs.artist_id')
                ->whereNull('albums.artist_id');

            $results = [
                'albums' => $albumQuery->get('albums.*'),
                'artists' => $artistQuery->get('artists.*'),
            ];

            if (!$dryRun) {
                $results['albums']->unsearchable(); // @phpstan-ignore-line
                $results['artists']->unsearchable(); // @phpstan-ignore-line
                $albumQuery->delete();
                $artistQuery->delete();
            }

            return $results;
        });
    }
}
