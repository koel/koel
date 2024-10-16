import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { songStore } from '@/stores/songStore'

export const mediaInfoService = {
  async fetchForArtist (artist: Artist) {
    artist = artistStore.syncWithVault(artist)[0]
    const cacheKey = ['artist.info', artist.id]
    if (cache.has(cacheKey)) {
      return cache.get<ArtistInfo>(cacheKey)
    }

    const info = await http.get<ArtistInfo | null>(`artists/${artist.id}/information`)

    info && cache.set(cacheKey, info)
    info?.image && (artist.image = info.image)

    return info
  },

  async fetchForAlbum (album: Album) {
    album = albumStore.syncWithVault(album)[0]
    const cacheKey = ['album.info', album.id]
    if (cache.has(cacheKey)) {
      return cache.get<AlbumInfo>(cacheKey)
    }

    const info = await http.get<AlbumInfo | null>(`albums/${album.id}/information`)
    info && cache.set(cacheKey, info)

    if (info?.cover) {
      album.cover = info.cover
      songStore.byAlbum(album).forEach(song => (song.album_cover = info.cover!))
    }

    return info
  },
}
