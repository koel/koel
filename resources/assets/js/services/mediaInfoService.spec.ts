import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { cache, httpService } from '@/services'
import { albumStore, artistStore } from '@/stores'
import { mediaInfoService } from '@/services/mediaInfoService'

new class extends UnitTestCase {
  protected test () {
    it('fetches the artist info', async () => {
      const artist = artistStore.syncWithVault(factory<Artist>('artist', { id: 42 }))[0]
      const artistInfo = factory<ArtistInfo>('artist-info')
      const getMock = this.mock(httpService, 'get').mockResolvedValue(artistInfo)
      const hasCacheMock = this.mock(cache, 'has', false)
      const setCacheMock = this.mock(cache, 'set')

      await mediaInfoService.fetchForArtist(artist)

      expect(getMock).toHaveBeenCalledWith('artists/42/information')
      expect(hasCacheMock).toHaveBeenCalledWith(['artist.info', 42])
      expect(setCacheMock).toHaveBeenCalledWith(['artist.info', 42], artistInfo)
      expect(artist.image).toBe(artistInfo.image)
    })

    it('gets the artist info from cache', async () => {
      const artistInfo = factory<ArtistInfo>('artist-info')
      const hasCacheMock = this.mock(cache, 'has', true)
      const getCacheMock = this.mock(cache, 'get', artistInfo)
      const getMock = this.mock(httpService, 'get')

      expect(await mediaInfoService.fetchForArtist(factory<Artist>('artist', { id: 42 }))).toBe(artistInfo)
      expect(hasCacheMock).toHaveBeenCalledWith(['artist.info', 42])
      expect(getCacheMock).toHaveBeenCalledWith(['artist.info', 42])
      expect(getMock).not.toHaveBeenCalled()
    })

    it('fetches the album info', async () => {
      const album = albumStore.syncWithVault(factory<Album>('album', { id: 42 }))[0]
      const albumInfo = factory<AlbumInfo>('album-info')
      const getMock = this.mock(httpService, 'get').mockResolvedValue(albumInfo)
      const hasCacheMock = this.mock(cache, 'has', false)
      const setCacheMock = this.mock(cache, 'set')

      await mediaInfoService.fetchForAlbum(album)

      expect(getMock).toHaveBeenCalledWith('albums/42/information')
      expect(hasCacheMock).toHaveBeenCalledWith(['album.info', 42])
      expect(setCacheMock).toHaveBeenCalledWith(['album.info', 42], albumInfo)
      expect(album.cover).toBe(albumInfo.cover)
    })

    it('gets the album info from cache', async () => {
      const albumInfo = factory<AlbumInfo>('album-info')
      const hasCacheMock = this.mock(cache, 'has', true)
      const getCacheMock = this.mock(cache, 'get', albumInfo)
      const getMock = this.mock(httpService, 'get')

      expect(await mediaInfoService.fetchForAlbum(factory<Album>('album', { id: 42 }))).toBe(albumInfo)
      expect(hasCacheMock).toHaveBeenCalledWith(['album.info', 42])
      expect(getCacheMock).toHaveBeenCalledWith(['album.info', 42])
      expect(getMock).not.toHaveBeenCalled()
    })
  }
}
