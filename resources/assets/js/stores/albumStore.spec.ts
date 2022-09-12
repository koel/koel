import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore, songStore } from '@/stores'
import factory from '@/__tests__/factory'
import { httpService } from '@/services'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      albumStore.vault.clear()
      albumStore.state.albums = []
    })
  }

  protected test () {
    it('gets an album by ID', () => {
      const album = factory<Album>('album')
      albumStore.vault.set(album.id, album)
      expect(albumStore.byId(album.id)).toEqual(album)
    })

    it('removes albums by IDs', () => {
      const albums = factory<Album>('album', 3)
      albums.forEach(album => albumStore.vault.set(album.id, album))
      albumStore.state.albums = albums

      albumStore.removeByIds([albums[0].id, albums[1].id])

      expect(albumStore.vault.size).toBe(1)
      expect(albumStore.vault.has(albums[0].id)).toBe(false)
      expect(albumStore.vault.has(albums[1].id)).toBe(false)
      expect(albumStore.state.albums).toEqual([albums[2]])
    })

    it('identifies an unknown album', () => {
      const album = factory.states('unknown')<Album>('album')

      expect(albumStore.isUnknown(album)).toBe(true)
      expect(albumStore.isUnknown(album.id)).toBe(true)
      expect(albumStore.isUnknown(factory<Album>('album'))).toBe(false)
    })

    it('syncs albums with the vault', () => {
      const album = factory<Album>('album', { name: 'IV' })

      albumStore.syncWithVault(album)
      expect(albumStore.vault.get(album.id)).toEqual(album)

      album.name = 'V'
      albumStore.syncWithVault(album)

      expect(albumStore.vault.size).toBe(1)
      expect(albumStore.vault.get(album.id)?.name).toBe('V')
    })

    it('uploads a cover for an album', async () => {
      const album = factory<Album>('album')
      albumStore.syncWithVault(album)
      const songsInAlbum = factory<Song>('song', 3, { album_id: album.id })
      const putMock = this.mock(httpService, 'put').mockResolvedValue({ coverUrl: 'http://localhost/cover.jpg' })
      this.mock(songStore, 'byAlbum', songsInAlbum)

      await albumStore.uploadCover(album, 'data://cover')

      expect(album.cover).toBe('http://localhost/cover.jpg')
      expect(putMock).toHaveBeenCalledWith(`album/${album.id}/cover`, { cover: 'data://cover' })
      expect(albumStore.byId(album.id)?.cover).toBe('http://localhost/cover.jpg')
      songsInAlbum.forEach(song => expect(song.album_cover).toBe('http://localhost/cover.jpg'))
    })

    it('fetches an album thumbnail', async () => {
      const getMock = this.mock(httpService, 'get').mockResolvedValue({ thumbnailUrl: 'http://localhost/thumbnail.jpg' })
      const album = factory<Album>('album')

      const url = await albumStore.fetchThumbnail(album.id)

      expect(getMock).toHaveBeenCalledWith(`album/${album.id}/thumbnail`)
      expect(url).toBe('http://localhost/thumbnail.jpg')
    })

    it('resolves an album', async () => {
      const album = factory<Album>('album')
      const getMock = this.mock(httpService, 'get').mockResolvedValueOnce(album)

      expect(await albumStore.resolve(album.id)).toEqual(album)
      expect(getMock).toHaveBeenCalledWith(`albums/${album.id}`)

      // next call shouldn't make another request
      expect(await albumStore.resolve(album.id)).toEqual(album)
      expect(getMock).toHaveBeenCalledOnce()
    })

    it('paginates', async () => {
      const albums = factory<Album>('album', 3)

      this.mock(httpService, 'get').mockResolvedValueOnce({
        data: albums,
        links: {
          next: '/albums?page=2'
        },
        meta: {
          current_page: 1
        }
      })

      expect(await albumStore.paginate(1)).toEqual(2)
      expect(albumStore.state.albums).toEqual(albums)
      expect(albumStore.vault.size).toBe(3)
    })
  }
}
