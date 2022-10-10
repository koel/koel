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
    public function updateSongs(array $songIds, SongUpdateData $data): Collection
    {
        $updatedSongs = collect();

        DB::transaction(function () use ($songIds, $data, $updatedSongs): void {
            foreach ($songIds as $id) {
                /** @var Song|null $song */
                $song = Song::with('album', 'album.artist', 'artist')->find($id);

                if ($song) {
                    $updatedSongs->push($this->updateSong($song, $data));
                }
            }
        });

        return $updatedSongs;
    }

    private function updateSong(Song $song, SongUpdateData $data): Song
    {
        $maybeSetAlbumArtist = static function (Album $album) use ($data): void {
            if ($data->albumArtistName) {
                $album->artist_id = Artist::getOrCreate($data->albumArtistName)->id;
                $album->save();
            }
        };

        $maybeSetAlbum = static function () use ($data, $song, $maybeSetAlbumArtist): void {
            if ($data->albumName) {
                $album = Album::getOrCreate($song->artist, $data->albumName);
                $song->album_id = $album->id;

                $maybeSetAlbumArtist($album);
            }
        };

        // if album artist name is provided, get/create an album with that artist and assign it to the song
        if ($data->albumArtistName) {
            $album = Album::getOrCreate(Artist::getOrCreate($data->albumArtistName), $data->albumName);
            $song->album_id = $album->id;
        } else {
            $maybeSetAlbum();
        }

        if ($data->artistName) {
            $artist = Artist::getOrCreate($data->artistName);
            $song->artist_id = $artist->id;
        } else {
            $maybeSetAlbum();
        }

        // For string attributes like title, lyrics, and genre, we use "??" because empty strings still have effects
        $song->title = $data->title ?? $song->title;
        $song->lyrics = $data->lyrics ?? $song->lyrics;
        $song->genre = $data->genre ?? $song->genre;

        $song->track = $data->track ?: $song->track;
        $song->year = $data->year ?: $song->year;
        $song->disc = $data->disc ?: $song->disc;

        $song->push();

        return $this->songRepository->getOne($song->id);
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
