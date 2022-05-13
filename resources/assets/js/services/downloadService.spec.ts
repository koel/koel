import { favoriteStore, playlistStore } from '@/stores'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { downloadService } from './downloadService'

new class extends UnitTestCase {
  protected test () {
    it('downloads songs', () => {
      const mock = this.mock(downloadService, 'trigger')
      downloadService.fromSongs([factory<Song>('song', { id: 'foo' }), factory<Song>('song', { id: 'bar' })])

      expect(mock).toHaveBeenCalledWith('songs?songs[]=bar&songs[]=foo&')
    })

    it('downloads all by artist', () => {
      const mock = this.mock(downloadService, 'trigger')
      downloadService.fromArtist(factory<Artist>('artist', { id: 42 }))

      expect(mock).toHaveBeenCalledWith('artist/42')
    })

    it('downloads all in album', () => {
      const mock = this.mock(downloadService, 'trigger')
      downloadService.fromAlbum(factory<Album>('album', { id: 42 }))

      expect(mock).toHaveBeenCalledWith('album/42')
    })

    it('downloads a playlist', () => {
      const getSongsMock = this.mock(playlistStore, 'getSongs', factory<Song>('song', 5))
      const mock = this.mock(downloadService, 'trigger')
      const playlist = factory<Playlist>('playlist', { id: 42 })

      downloadService.fromPlaylist(playlist)

      expect(getSongsMock).toHaveBeenCalledWith(playlist)
      expect(mock).toHaveBeenCalledWith('playlist/42')
    })

    it('does not download an empty playlist', () => {
      const getSongsMock = this.mock(playlistStore, 'getSongs', [])
      const triggerMock = this.mock(downloadService, 'trigger')
      const playlist = factory<Playlist>('playlist')

      downloadService.fromPlaylist(playlist)

      expect(getSongsMock).toHaveBeenCalledWith(playlist)
      expect(triggerMock).not.toHaveBeenCalled()
    })

    it.each<[Song[], boolean]>([[[], false], [factory<Song>('song', 5), true]])(
      'downloads favorites if available',
      (songs, triggered) => {
        const mock = this.mock(downloadService, 'trigger')
        favoriteStore.all = songs

        downloadService.fromFavorites()

        triggered ? expect(mock).toHaveBeenCalledWith('favorites') : expect(mock).not.toHaveBeenCalled()
      })
  }
}
