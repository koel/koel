import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { albumStore } from '@/stores/albumStore'

describe('albumStore', () => {
  const h = createHarness({
    beforeEach: () => {
      albumStore.vault.clear()
      albumStore.state.albums = []
    },
  })

  it('gets an album by ID', () => {
    const album = h.factory('album')
    albumStore.vault.set(album.id, album)
    expect(albumStore.byId(album.id)).toEqual(album)
  })

  it('removes albums by IDs', () => {
    const albums = h.factory('album', 3)
    albums.forEach(album => albumStore.vault.set(album.id, album))
    albumStore.state.albums = albums

    albumStore.removeByIds([albums[0].id, albums[1].id])

    expect(albumStore.vault.size).toBe(1)
    expect(albumStore.vault.has(albums[0].id)).toBe(false)
    expect(albumStore.vault.has(albums[1].id)).toBe(false)
    expect(albumStore.state.albums).toEqual([albums[2]])
  })

  it('identifies an unknown album', () => {
    const album = factory.states('unknown')('album')

    expect(albumStore.isUnknown(album)).toBe(true)
    expect(albumStore.isUnknown(h.factory('album'))).toBe(false)
  })

  it('syncs albums with the vault', () => {
    const album = h.factory('album', { name: 'IV' })

    albumStore.syncWithVault(album)
    expect(albumStore.vault.get(album.id)).toEqual(album)

    album.name = 'V'
    albumStore.syncWithVault(album)

    expect(albumStore.vault.size).toBe(1)
    expect(albumStore.vault.get(album.id)?.name).toBe('V')
  })

  it('uploads a cover for an album', async () => {
    const album = h.factory('album')
    albumStore.syncWithVault(album)
    const songsInAlbum = h.factory('song', 3, { album_id: album.id })
    const putMock = h.mock(http, 'put').mockResolvedValue({ cover_url: 'http://test/cover.jpg' })
    h.mock(playableStore, 'byAlbum', songsInAlbum)

    await albumStore.uploadCover(album, 'data://cover')

    expect(album.cover).toBe('http://test/cover.jpg')
    expect(putMock).toHaveBeenCalledWith(`albums/${album.id}/cover`, { cover: 'data://cover' })
    expect(albumStore.byId(album.id)?.cover).toBe('http://test/cover.jpg')
    songsInAlbum.forEach(song => expect(song.album_cover).toBe('http://test/cover.jpg'))
  })

  it('fetches an album thumbnail', async () => {
    const getMock = h.mock(http, 'get').mockResolvedValue({ thumbnailUrl: 'http://test/thumbnail.jpg' })
    const album = h.factory('album')

    const url = await albumStore.fetchThumbnail(album.id)

    expect(getMock).toHaveBeenCalledWith(`albums/${album.id}/thumbnail`)
    expect(url).toBe('http://test/thumbnail.jpg')
  })

  it('resolves an album', async () => {
    const album = h.factory('album')
    const getMock = h.mock(http, 'get').mockResolvedValueOnce(album)

    expect(await albumStore.resolve(album.id)).toEqual(album)
    expect(getMock).toHaveBeenCalledWith(`albums/${album.id}`)

    // the next call shouldn't make another request
    expect(await albumStore.resolve(album.id)).toEqual(album)
    expect(getMock).toHaveBeenCalledOnce()
  })

  it('paginates', async () => {
    const albums = h.factory('album', 3)

    h.mock(http, 'get').mockResolvedValueOnce({
      data: albums,
      links: {
        next: '/albums?page=2',
      },
      meta: {
        current_page: 1,
      },
    })

    expect(await albumStore.paginate({
      favorites_only: false,
      sort: 'name',
      order: 'asc',
      page: 1,
    })).toEqual(2)

    expect(albumStore.state.albums).toEqual(albums)
    expect(albumStore.vault.size).toBe(3)
  })

  it('updates', async () => {
    const album = h.factory('album', { name: 'IV' })
    albumStore.syncWithVault(album)

    const updateData = {
      name: 'V',
      year: 2010,
    }

    const putMock = h.mock(http, 'put').mockResolvedValueOnce({ ...album, ...updateData })
    const updateAlbumNameMock = h.mock(playableStore, 'updateAlbumName')

    await albumStore.update(album, updateData)

    expect(putMock).toHaveBeenCalledWith(`albums/${album.id}`, updateData)
    expect(albumStore.vault.get(album.id)?.name).toBe(updateData.name)
    expect(albumStore.vault.get(album.id)?.year).toBe(updateData.year)
    expect(updateAlbumNameMock).toHaveBeenCalledWith(album, updateData.name)
  })

  it('toggles favorite', async () => {
    const album = h.factory('album', { favorite: false })
    albumStore.syncWithVault(album)

    const postMock = h.mock(http, 'post').mockResolvedValueOnce(h.factory('favorite', {
      favoriteable_type: 'album',
      favoriteable_id: album.id,
    }))

    await albumStore.toggleFavorite(album)

    expect(postMock).toHaveBeenCalledWith('favorites/toggle', { type: 'album', id: album.id })
    expect(album.favorite).toBe(true)

    postMock.mockResolvedValue(null)
    await albumStore.toggleFavorite(album)

    expect(postMock).toHaveBeenNthCalledWith(2, 'favorites/toggle', { type: 'album', id: album.id })
    expect(album.favorite).toBe(false)
  })
})
