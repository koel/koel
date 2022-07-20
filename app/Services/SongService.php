<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\SongUpdateData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SongService
{
    public function __construct(private SongRepository $songRepository)
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

                if (!$song) {
                    continue;
                }

                $updatedSongs->push($this->updateSong($song, $data));
            }
        });

        return $updatedSongs;
    }

    private function updateSong(Song $song, SongUpdateData $data): Song
    {
        $maybeSetAlbumArtist = static function (Album $album) use ($data): void {
            if ($data->albumArtistName && $data->albumArtistName !== $album->artist->name) {
                $album->artist_id = Artist::getOrCreate($data->albumArtistName)->id;
                $album->save();
            }
        };

        $maybeSetAlbum = static function () use ($data, $song, $maybeSetAlbumArtist): void {
            if ($data->albumName) {
                if ($data->albumName !== $song->album->name) {
                    $album = Album::getOrCreate($song->artist, $data->albumName);
                    $song->album_id = $album->id;

                    $maybeSetAlbumArtist($album);
                }
            }
        };

        if ($data->artistName) {
            if ($song->artist->name !== $data->artistName) {
                $artist = Artist::getOrCreate($data->artistName);
                $song->artist_id = $artist->id;

                // Artist changed means album must be changed too.
                $album = Album::getOrCreate($artist, $data->albumName ?: $song->album->name);
                $song->album_id = $album->id;

                $maybeSetAlbumArtist($album);
            } else {
                $maybeSetAlbum();
            }
        } else {
            $maybeSetAlbum();
        }

        $song->title = $data->title ?? $song->title; // Empty string still has effects
        $song->lyrics = $data->lyrics ?? $song->lyrics; // Empty string still has effects
        $song->track = $data->track ?: $song->track;
        $song->disc = $data->disc ?: $song->disc;

        $song->push();

        return $this->songRepository->getOne($song->id);
    }
}
