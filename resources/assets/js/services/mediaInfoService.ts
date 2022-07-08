import { Cache, httpService } from '@/services'

export const mediaInfoService = {
  async fetchForArtist (artist: Artist) {
    const cacheKey = ['artist.info', artist.id]
    if (Cache.has(cacheKey)) return Cache.get<ArtistInfo>(cacheKey)

    const info = await httpService.get<ArtistInfo | null>(`artists/${artist.id}/information`)
    info && Cache.set(cacheKey, info)

    return info
  },

  async fetchForAlbum (album: Album) {
    const cacheKey = ['album.info', album.id]
    if (Cache.has(cacheKey)) return Cache.get<AlbumInfo>(cacheKey)

    const info = await httpService.get<AlbumInfo | null>(`albums/${album.id}/information`)
    info && Cache.set(cacheKey, info)

    return info
  }
}
