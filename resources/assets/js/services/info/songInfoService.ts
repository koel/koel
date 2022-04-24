import { httpService, albumInfoService, artistInfoService } from '..'

interface SongInfoResponse {
  artist_info: ArtistInfo,
  album_info: AlbumInfo,
  youtube: {
    items: YouTubeVideo[],
    nextPageToken: string
  },
  lyrics: string
}

export const songInfo = {
  fetch: async (song: Song): Promise<Song> => {
    if (!song.infoRetrieved) {
      const { lyrics, artist_info, album_info, youtube } = await httpService.get<SongInfoResponse>(`song/${song.id}/info`)

      song.lyrics = lyrics
      artist_info && artistInfoService.merge(song.artist, artist_info)
      album_info && albumInfoService.merge(song.album, album_info)
      song.youtube = youtube
      song.infoRetrieved = true
    }

    return song
  }
}
