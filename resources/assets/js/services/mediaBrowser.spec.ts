import { expect, it, vi } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import { songStore } from '@/stores/songStore'
import { mediaBrowser } from '@/services/mediaBrowser'
import { cache } from '@/services/cache'
import { commonStore } from '@/stores/commonStore'

new class extends UnitTestCase {
  protected test () {
    it('browses a path', async () => {
      const path = 'foo/bar'
      const folders = factory('folder', 2)
      const songs = factory('song', 3)

      const songPaginator: PaginatorResource<Song> = {
        data: songs,
        links: {
          next: 'foo',
        },
        meta: {
          current_page: 2,
        },
      }

      const getMock = this.mock(http, 'get')
        .mockResolvedValueOnce(folders)
        .mockResolvedValueOnce(songPaginator)

      const syncWithVaultMock = this.mock(songStore, 'syncWithVault').mockReturnValue(songs)

      const result = await mediaBrowser.browse(path, 2)

      expect(getMock).toBeCalledTimes(2)
      expect(getMock).toHaveBeenNthCalledWith(1, `browse/folders?path=${path}`)
      expect(getMock).toHaveBeenNthCalledWith(2, `browse/songs?path=${path}&page=2`)
      expect(syncWithVaultMock).toHaveBeenCalledWith(songs)

      expect(result).toEqual({
        subfolders: folders,
        songs,
        nextPage: 3,
      })
    })

    it('get from cache when available', async () => {
      const path = 'foo/bar'
      const folders = factory('folder', 2)
      const songs = factory('song', 3)

      const songPaginator: PaginatorResource<Song> = {
        data: songs,
        links: {
          next: 'foo',
        },
        meta: {
          current_page: 2,
        },
      }

      cache.set(['folder', path, 'folders'], folders)
      cache.set(['folder', path, 'songs', 2], songPaginator)

      const getMock = this.mock(http, 'get')

      const syncWithVaultMock = this.mock(songStore, 'syncWithVault').mockReturnValue(songs)

      const result = await mediaBrowser.browse(path, 2)

      expect(getMock).not.toHaveBeenCalled()
      expect(syncWithVaultMock).toHaveBeenCalledWith(songs)

      expect(result).toEqual({
        subfolders: folders,
        songs,
        nextPage: 3,
      })
    })

    it('clears the cache if forced to', async () => {
      const removeCacheMock = vi.spyOn(cache, 'remove')

      const path = 'foo/bar'
      const folders = factory('folder', 2)
      const songs = factory('song', 3)

      const songPaginator: PaginatorResource<Song> = {
        data: songs,
        links: {
          next: 'foo',
        },
        meta: {
          current_page: 2,
        },
      }

      const getMock = this.mock(http, 'get')
        .mockResolvedValueOnce(folders)
        .mockResolvedValueOnce(songPaginator)

      const syncWithVaultMock = this.mock(songStore, 'syncWithVault').mockReturnValue(songs)

      const result = await mediaBrowser.browse(path, 2, true)

      expect(removeCacheMock).toBeCalledTimes(2)
      expect(removeCacheMock).toHaveBeenNthCalledWith(1, ['folder', path, 'folders'])
      expect(removeCacheMock).toHaveBeenNthCalledWith(2, ['folder', path, 'songs', 2])

      expect(getMock).toBeCalledTimes(2)
      expect(getMock).toHaveBeenNthCalledWith(1, `browse/folders?path=${path}`)
      expect(getMock).toHaveBeenNthCalledWith(2, `browse/songs?path=${path}&page=2`)
      expect(syncWithVaultMock).toHaveBeenCalledWith(songs)

      expect(result).toEqual({
        subfolders: folders,
        songs,
        nextPage: 3,
      })
    })

    it('generates breadcrumbs', () => {
      commonStore.state.dir_separator = '/'

      expect(mediaBrowser.generateBreadcrumbs('foo/bar/baz')).toEqual([
        { name: 'Library', path: '' },
        { name: 'foo', path: 'foo' },
        { name: 'bar', path: 'foo/bar' },
        { name: 'baz', path: 'foo/bar/baz' },
      ])

      expect(mediaBrowser.generateBreadcrumbs('')).toEqual([
        { name: 'Library', path: '' },
      ])
    })

    it.each([
      [null, null],
      ['', null],
      ['/', null],
      ['foo', ''],
      ['foo/bar', 'foo'],
      ['foo/bar/baz', 'foo/bar'],
      ['foo/bar/baz/', 'foo/bar'],
    ])('gets the parent folder for %s', (path, parentPath) => {
      commonStore.state.dir_separator = '/'

      const expectedParentFolder = parentPath === null
        ? null
        : {
            type: 'folders',
            id: '..',
            parent_id: '..',
            name: '..',
            path: parentPath,
          }

      expect(mediaBrowser.getParentFolder(path)).toEqual(expectedParentFolder)
    })

    it('extracts media references', () => {
      const items = [
        factory('song'),
        factory('folder', 1),
      ]

      const references = mediaBrowser.extractMediaReferences(items)

      expect(references).toEqual([
        { id: items[0].id, type: 'songs' },
        { path: items[1].path, type: 'folders' }, // @ts-expect-error
      ])
    })
  }
}
