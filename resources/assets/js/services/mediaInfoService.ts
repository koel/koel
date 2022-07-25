import { cache, httpService } from '@/services'
import { albumStore, artistStore, songStore } from '@/stores'

export const mediaInfoService = {
  async fetchForArtist (artist: Artist) {
    const cacheKey = ['artist.info', artist.id]
    if (cache.has(cacheKey)) return cache.get<ArtistInfo>(cacheKey)

    const info = await httpService.get<ArtistInfo | null>(`artists/${artist.id}/information`)
    info && cache.set(cacheKey, info)

    if (info?.image) {
      artistStore.byId(artist.id)!.image = info.image
    }

    return info
  },

  async fetchForAlbum (album: Album) {
    const cacheKey = ['album.info', album.id]
    if (cache.has(cacheKey)) return cache.get<AlbumInfo>(cacheKey)

    const info = await httpService.get<AlbumInfo | null>(`albums/${album.id}/information`)
    info && cache.set(cacheKey, info)

    if (info?.cover) {
      albumStore.byId(album.id)!.cover = info.cover
      songStore.byAlbum(album)!.forEach(song => (song.album_cover = info.cover))
    }

    return info
  }
}
