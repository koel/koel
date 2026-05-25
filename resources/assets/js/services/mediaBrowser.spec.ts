import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { mediaBrowser } from '@/services/mediaBrowser'
import { cache } from '@/services/cache'

describe('mediaBrowser', () => {
  const h = createHarness()

  it('browses a folder', async () => {
    const current = h.factory('folder').make()
    const folderId = current.id
    const ancestors = h.factory('folder').make(2)
    const subfolders = h.factory('folder').make(3)
    const songs = h.factory('song').make(3)

    const songPaginator: PaginatorResource<Song> = {
      data: songs,
      links: {
        next: 'foo',
      },
      meta: {
        current_page: 2,
      },
    }

    const getMock = h
      .mock(http, 'get')
      .mockResolvedValueOnce({ current, ancestors, subfolders })
      .mockResolvedValueOnce(songPaginator)

    const syncWithVaultMock = h.mock(playableStore, 'syncWithVault').mockReturnValue(songs)

    const result = await mediaBrowser.browse(folderId, 2)

    expect(getMock).toBeCalledTimes(2)
    expect(getMock).toHaveBeenNthCalledWith(1, `browse/folders?folder=${folderId}`)
    expect(getMock).toHaveBeenNthCalledWith(2, `browse/songs?folder=${folderId}&page=2`)
    expect(syncWithVaultMock).toHaveBeenCalledWith(songs)

    expect(result).toEqual({
      current,
      ancestors,
      subfolders,
      songs,
      nextPage: 3,
    })
  })

  it('browses the library root', async () => {
    const subfolders = h.factory('folder').make(3)
    const songs = h.factory('song').make(2)

    const songPaginator: PaginatorResource<Song> = {
      data: songs,
      links: { next: null },
      meta: { current_page: 1 },
    }

    const getMock = h
      .mock(http, 'get')
      .mockResolvedValueOnce({ current: null, ancestors: [], subfolders })
      .mockResolvedValueOnce(songPaginator)

    h.mock(playableStore, 'syncWithVault').mockReturnValue(songs)

    const result = await mediaBrowser.browse(null, 1)

    expect(getMock).toHaveBeenNthCalledWith(1, 'browse/folders')
    expect(getMock).toHaveBeenNthCalledWith(2, 'browse/songs?page=1')

    expect(result).toEqual({
      current: null,
      ancestors: [],
      subfolders,
      songs,
      nextPage: null,
    })
  })

  it('get from cache when available', async () => {
    const current = h.factory('folder').make()
    const folderId = current.id
    const ancestors = h.factory('folder').make(1)
    const subfolders = h.factory('folder').make(2)
    const songs = h.factory('song').make(3)

    const songPaginator: PaginatorResource<Song> = {
      data: songs,
      links: {
        next: 'foo',
      },
      meta: {
        current_page: 2,
      },
    }

    cache.set(['folder', folderId, 'folders'], { current, ancestors, subfolders })
    cache.set(['folder', folderId, 'songs', 2], songPaginator)

    const getMock = h.mock(http, 'get')

    const syncWithVaultMock = h.mock(playableStore, 'syncWithVault').mockReturnValue(songs)

    const firstResult = await mediaBrowser.browse(folderId, 2)
    const secondResult = await mediaBrowser.browse(folderId, 2)

    expect(getMock).not.toHaveBeenCalled()
    expect(syncWithVaultMock).toHaveBeenCalledWith(songs)

    const expected = {
      current,
      ancestors,
      subfolders,
      songs,
      nextPage: 3,
    }

    expect(firstResult).toEqual(expected)
    expect(secondResult).toEqual(expected)
  })

  it('clears the cache if forced to', async () => {
    const removeCacheMock = vi.spyOn(cache, 'remove')

    const current = h.factory('folder').make()
    const folderId = current.id
    const subfolders = h.factory('folder').make(2)
    const songs = h.factory('song').make(3)

    const songPaginator: PaginatorResource<Song> = {
      data: songs,
      links: {
        next: 'foo',
      },
      meta: {
        current_page: 2,
      },
    }

    const getMock = h
      .mock(http, 'get')
      .mockResolvedValueOnce({ current, ancestors: [], subfolders })
      .mockResolvedValueOnce(songPaginator)

    h.mock(playableStore, 'syncWithVault').mockReturnValue(songs)

    const result = await mediaBrowser.browse(folderId, 2, true)

    expect(removeCacheMock).toBeCalledTimes(2)
    expect(removeCacheMock).toHaveBeenNthCalledWith(1, ['folder', folderId, 'folders'])
    expect(removeCacheMock).toHaveBeenNthCalledWith(2, ['folder', folderId, 'songs', 2])

    expect(getMock).toBeCalledTimes(2)
    expect(getMock).toHaveBeenNthCalledWith(1, `browse/folders?folder=${folderId}`)
    expect(getMock).toHaveBeenNthCalledWith(2, `browse/songs?folder=${folderId}&page=2`)

    expect(result).toEqual({
      current,
      ancestors: [],
      subfolders,
      songs,
      nextPage: 3,
    })
  })

  it('returns null parent reference for null folder', () => {
    expect(mediaBrowser.getParentReference(null)).toBeNull()
  })

  it('builds a parent reference for a folder with a parent', () => {
    const parent = h.factory('folder').make()
    const folder = h.factory('folder').make({ parent_id: parent.id })

    expect(mediaBrowser.getParentReference(folder)).toEqual({
      type: 'folders',
      id: parent.id,
      parent_id: null,
      name: '..',
      is_uploads: false,
    })
  })

  it('builds a parent reference pointing to the library root', () => {
    const folder = h.factory('folder').make({ parent_id: null })

    expect(mediaBrowser.getParentReference(folder)).toEqual({
      type: 'folders',
      id: '',
      parent_id: null,
      name: '..',
      is_uploads: false,
    })
  })

  it('extracts media references', () => {
    const items = [h.factory('song').make(), h.factory('folder').make()]

    expect(mediaBrowser.extractMediaReferences(items)).toEqual([
      { id: items[0].id, type: 'songs' },
      { id: items[1].id, type: 'folders' },
    ])
  })
})
