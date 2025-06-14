import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { mediaInfoService } from './mediaInfoService'

new class extends UnitTestCase {
  protected test () {
    it('fetches the artist info', async () => {
      const artist = artistStore.syncWithVault(factory('artist'))[0]
      const artistInfo = factory('artist-info')
      const getMock = this.mock(http, 'get').mockResolvedValue(artistInfo)
      const hasCacheMock = this.mock(cache, 'has', false)
      const setCacheMock = this.mock(cache, 'set')

      await mediaInfoService.fetchForArtist(artist)

      expect(getMock).toHaveBeenCalledWith(`artists/${artist.id}/information`)
      expect(hasCacheMock).toHaveBeenCalledWith(['artist.info', artist.id])
      expect(setCacheMock).toHaveBeenCalledWith(['artist.info', artist.id], artistInfo)
      expect(artist.image).toBe(artistInfo.image)
    })

    it('gets the artist info from cache', async () => {
      const artistInfo = factory('artist-info')
      const hasCacheMock = this.mock(cache, 'has', true)
      const getCacheMock = this.mock(cache, 'get', artistInfo)
      const getMock = this.mock(http, 'get')

      const artist = artistStore.syncWithVault(factory('artist'))[0]

      expect(await mediaInfoService.fetchForArtist(artist)).toBe(artistInfo)
      expect(hasCacheMock).toHaveBeenCalledWith(['artist.info', artist.id])
      expect(getCacheMock).toHaveBeenCalledWith(['artist.info', artist.id])
      expect(getMock).not.toHaveBeenCalled()
    })

    it('fetches the album info', async () => {
      const album = albumStore.syncWithVault(factory('album'))[0]
      const albumInfo = factory('album-info')
      const getMock = this.mock(http, 'get').mockResolvedValue(albumInfo)
      const hasCacheMock = this.mock(cache, 'has', false)
      const setCacheMock = this.mock(cache, 'set')

      await mediaInfoService.fetchForAlbum(album)

      expect(getMock).toHaveBeenCalledWith(`albums/${album.id}/information`)
      expect(hasCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name])
      expect(setCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name], albumInfo)
      expect(album.cover).toBe(albumInfo.cover)
    })

    it('gets the album info from cache', async () => {
      const album = albumStore.syncWithVault(factory('album'))[0]
      const albumInfo = factory('album-info')
      const hasCacheMock = this.mock(cache, 'has', true)
      const getCacheMock = this.mock(cache, 'get', albumInfo)
      const getMock = this.mock(http, 'get')

      expect(await mediaInfoService.fetchForAlbum(album)).toBe(albumInfo)
      expect(hasCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name])
      expect(getCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name])
      expect(getMock).not.toHaveBeenCalled()
    })
  }
}
