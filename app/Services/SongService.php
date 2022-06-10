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
        if ($data->artistName && $song->artist->name !== $data->artistName) {
            $song->artist_id = Artist::getOrCreate($data->artistName)->id;
        }

        if ($data->albumName || $data->albumArtistName) {
            $albumArtist = $data->albumArtistName ? Artist::getOrCreate($data->albumArtistName) : $song->album->artist;
            $song->album_id = Album::getOrCreate($albumArtist, $data->albumName)->id;
        }

        $song->title = $data->title ?: $song->title;
        $song->lyrics = $data->lyrics ?: $song->lyrics;
        $song->track = $data->track ?: $song->track;
        $song->disc = $data->disc ?: $song->disc;

        $song->save();

        return $this->songRepository->getOne($song->id);
    }
}
