import { albumInfoService, artistInfoService, httpService } from '..'

interface SongInfoResponse {
  artist_info: ArtistInfo,
  album_info: AlbumInfo,
  youtube: {
    items: YouTubeVideo[],
    nextPageToken: string
  },
  lyrics: string
}

export const songInfoService = {
  fetch: async (song: Song) => {
    if (!song.infoRetrieved) {
      const {
        lyrics,
        artist_info: artistInfo,
        album_info: albumInfo,
        youtube
      } = await httpService.get<SongInfoResponse>(`song/${song.id}/info`)

      song.lyrics = lyrics
      artistInfo && artistInfoService.merge(song.artist, artistInfo)
      albumInfo && albumInfoService.merge(song.album, albumInfo)
      song.youtube = youtube
      song.infoRetrieved = true
    }

    return song
  }
}
