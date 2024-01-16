<?php

namespace App\Services;

use App\Events\LibraryChanged;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\SongUpdateData;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Throwable;

class SongService
{
    public function __construct(private SongRepository $songRepository, private LoggerInterface $logger)
    {
    }

    /** @return Collection|array<array-key, Song> */
    public function updateSongs(array $ids, SongUpdateData $data): Collection
    {
        if (count($ids) === 1) {
            // If we're only updating one song, an empty non-required should be converted to the default values.
            // This allows the user to clear those fields.
            $data->disc = $data->disc ?: 1;
            $data->track = $data->track ?: 0;
            $data->lyrics = $data->lyrics ?: '';
            $data->year = $data->year ?: null;
            $data->genre = $data->genre ?: '';
            $data->albumArtistName = $data->albumArtistName ?: $data->artistName;
        }

        return DB::transaction(function () use ($ids, $data): Collection {
            return collect($ids)->reduce(function (Collection $updated, string $id) use ($data): Collection {
                /** @var Song|null $song */
                $song = Song::with('album', 'album.artist', 'artist')->find($id);

                if ($song) {
                    $updated->push($this->updateSong($song, clone $data));
                }

                return $updated;
            }, collect());
        });
    }

    private function updateSong(Song $song, SongUpdateData $data): Song
    {
        // for non-nullable fields, if the provided data is empty, use the existing value
        $data->albumName = $data->albumName ?: $song->album->name;
        $data->artistName = $data->artistName ?: $song->artist->name;
        $data->title = $data->title ?: $song->title;

        // For nullable fields, use the existing value only if the provided data is explicitly null.
        // This allows us to clear those fields.
        $data->albumArtistName ??= $song->album_artist->name;
        $data->lyrics ??= $song->lyrics;
        $data->track ??= $song->track;
        $data->disc ??= $song->disc;
        $data->genre ??= $song->genre;
        $data->year ??= $song->year;

        $albumArtist = Artist::getOrCreate($data->albumArtistName);
        $artist = Artist::getOrCreate($data->artistName);
        $album = Album::getOrCreate($albumArtist, $data->albumName);

        $song->album_id = $album->id;
        $song->artist_id = $artist->id;
        $song->title = $data->title;
        $song->lyrics = $data->lyrics;
        $song->track = $data->track;
        $song->disc = $data->disc;
        $song->genre = $data->genre;
        $song->year = $data->year;

        $song->push();

        return $this->songRepository->getOne($song->id);
    }

    public function publicizeSongs(Collection $songs): void
    {
        Song::query()->whereIn('id', $songs->pluck('id'))->update(['is_public' => true]);
    }

    public function privatizeSongs(Collection $songs): void
    {
        Song::query()->whereIn('id', $songs->pluck('id'))->update(['is_public' => false]);
    }

    /**
     * @param array<string>|string $ids
     */
    public function deleteSongs(array|string $ids): void
    {
        $ids = Arr::wrap($ids);

        DB::transaction(function () use ($ids): void {
            $shouldBackUp = config('koel.backup_on_delete');

            /** @var Collection|array<array-key, Song> $songs */
            $songs = Song::query()->findMany($ids);

            Song::destroy($ids);

            $songs->each(function (Song $song) use ($shouldBackUp): void {
                try {
                    if ($shouldBackUp) {
                        rename($song->path, $song->path . '.bak');
                    } else {
                        unlink($song->path);
                    }
                } catch (Throwable $e) {
                    $this->logger->error('Failed to remove song file', [
                        'path' => $song->path,
                        'exception' => $e,
                    ]);
                }
            });

            event(new LibraryChanged());
        });
    }
}
