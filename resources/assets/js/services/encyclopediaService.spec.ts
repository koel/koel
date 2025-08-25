import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { encyclopediaService } from './encyclopediaService'

describe('encyclopediaService', () => {
  const h = createHarness()

  it('fetches the artist info', async () => {
    const artist = artistStore.syncWithVault(h.factory('artist'))[0]
    const artistInfo = h.factory('artist-info')
    const getMock = h.mock(http, 'get').mockResolvedValue(artistInfo)
    const hasCacheMock = h.mock(cache, 'has', false)
    const setCacheMock = h.mock(cache, 'set')

    await encyclopediaService.fetchForArtist(artist)

    expect(getMock).toHaveBeenCalledWith(`artists/${artist.id}/information`)
    expect(hasCacheMock).toHaveBeenCalledWith(['artist.info', artist.id])
    expect(setCacheMock).toHaveBeenCalledWith(['artist.info', artist.id], artistInfo)
    expect(artist.image).toBe(artistInfo.image)
  })

  it('gets the artist info from cache', async () => {
    const artistInfo = h.factory('artist-info')
    const hasCacheMock = h.mock(cache, 'has', true)
    const getCacheMock = h.mock(cache, 'get', artistInfo)
    const getMock = h.mock(http, 'get')

    const artist = artistStore.syncWithVault(h.factory('artist'))[0]

    expect(await encyclopediaService.fetchForArtist(artist)).toBe(artistInfo)
    expect(hasCacheMock).toHaveBeenCalledWith(['artist.info', artist.id])
    expect(getCacheMock).toHaveBeenCalledWith(['artist.info', artist.id])
    expect(getMock).not.toHaveBeenCalled()
  })

  it('fetches the album info', async () => {
    const album = albumStore.syncWithVault(h.factory('album'))[0]
    const albumInfo = h.factory('album-info')
    const getMock = h.mock(http, 'get').mockResolvedValue(albumInfo)
    const hasCacheMock = h.mock(cache, 'has', false)
    const setCacheMock = h.mock(cache, 'set')

    await encyclopediaService.fetchForAlbum(album)

    expect(getMock).toHaveBeenCalledWith(`albums/${album.id}/information`)
    expect(hasCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name])
    expect(setCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name], albumInfo)
    expect(album.cover).toBe(albumInfo.cover)
  })

  it('gets the album info from cache', async () => {
    const album = albumStore.syncWithVault(h.factory('album'))[0]
    const albumInfo = h.factory('album-info')
    const hasCacheMock = h.mock(cache, 'has', true)
    const getCacheMock = h.mock(cache, 'get', albumInfo)
    const getMock = h.mock(http, 'get')

    expect(await encyclopediaService.fetchForAlbum(album)).toBe(albumInfo)
    expect(hasCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name])
    expect(getCacheMock).toHaveBeenCalledWith(['album.info', album.id, album.name])
    expect(getMock).not.toHaveBeenCalled()
  })
})
